package scratch

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"html/template"
	"io/fs"
	"log"
	"math/rand"
	"net/http"
	"net/url"
	"os"
	"path"
	"path/filepath"
	"strings"
	"time"

	"git.sr.ht/~mariusor/assets"
	ass "git.sr.ht/~mariusor/scratch/internal/assets"
)

type Handler struct {
	BasePath      storage
	Assets        assets.Map
	authorization map[string][]byte
}

var rnd = rand.New(rand.NewSource(time.Now().UnixNano()))

var unauthorizedErr = fmt.Errorf("missmatched key")

func (h Handler) CheckKey(r *http.Request) bool {
	key := getKeyFromRequest(r)
	path := getPathFromRequest(r)
	if _, err := h.BasePath.LoadKeyForPath(path); err != nil && errors.Is(err, fs.ErrNotExist) {
		return true
	}
	k, ok := h.authorization[path]
	return ok && bytes.Equal(k, key)
}

func getRandomKey() []byte {
	return []byte(
		base64.RawStdEncoding.Strict().EncodeToString([]byte(fmt.Sprintf("%d", rnd.Uint64()))),
	)
}

func (h *Handler) CheckOrUpdateKeyRequest(r *http.Request) (string, error) {
	path := getPathFromRequest(r)
	if err := r.ParseForm(); err != nil {
		return "", err
	}

	// We load the pw from POST data
	pw := []byte(r.PostFormValue("_"))
	genNewKey := false
	if !h.BasePath.CheckPwForPath(pw, path) {
		if !h.CheckKey(r) {
			return "", fmt.Errorf("unauthorized to save key %q: %w", path, unauthorizedErr)
		}
		if len(pw) > 0 {
			if err := h.BasePath.SaveKeyForPath(pw, path); err != nil {
				return "", fmt.Errorf("unable to save key %q: %w", path, err)
			}
			genNewKey = true
		}
	}

	key, ok := h.authorization[path]
	if genNewKey || !ok || len(key) == 0 {
		// Generate random auth token for current path.
		// We show it to the user, only if they send the proper pw
		key = getRandomKey()
	}

	h.authorization[path] = key
	log.Printf("Generated auth key for %s: %q", path, key)
	return string(key), nil
}

func (h Handler) DeleteRequest(r *http.Request) error {
	path := getPathFromRequest(r)
	if !h.CheckKey(r) {
		return fmt.Errorf("unauthorized to delete path %q: %w", path, unauthorizedErr)
	}

	return h.BasePath.DeletePath(path)
}

func (h Handler) UpdateRequest(r *http.Request) error {
	path := getPathFromRequest(r)
	if !h.CheckKey(r) {
		return fmt.Errorf("%s unauthorized: %w", path, unauthorizedErr)
	}
	content := r.PostFormValue("_")
	if r.PostForm.Has("_") {
		log.Printf("saving %dbytes", len(content))
		if err := h.BasePath.SavePath(content, path); err != nil {
			return err
		}
	}
	return nil
}

func statusError(err error) int {
	status := http.StatusInternalServerError
	if errors.Is(err, unauthorizedErr) {
		status = http.StatusUnauthorized
	}
	return status
}

func writeError(w http.ResponseWriter, err error) {
	log.Printf("Error: %s", err)
	http.Error(w, err.Error(), statusError(err))
}

func jsCSRF() template.JS {
	csrf := struct{}{}
	b, _ := json.Marshal(csrf)
	return template.JS(b)
}

type Index struct {
	Path  string
	Files []IndexEntry
}

type IndexEntry struct {
	parent    *Index
	Path      string
	ModTime   time.Time
	Size      int64
	HasSecret bool
}

func (i IndexEntry) URL() template.HTMLAttr {
	return template.HTMLAttr("./" + i.Path)
}

var errorRedirectToContent = fmt.Errorf("no children")

func styleNode(n string) template.HTML {
	return assets.StyleNode(ass.FS, n)
}

func scriptNode(n string) template.HTML {
	return assets.ScriptNode(ass.FS, n)
}

func iconForEntry(i IndexEntry) template.HTML {
	name := "unlock"
	if i.HasSecret {
		name = "lock"
	}
	return assets.Svg(ass.FS, name)
}

var (
	tplNames = []string{"index.html", "main.html"}
	helpers  = template.FuncMap{
		"style":  styleNode,
		"script": scriptNode,
		"icon":   iconForEntry,
		"csrf":   jsCSRF,
		"title":  func() template.HTMLAttr { return "Unknown" },
		"help":   func() template.HTMLAttr { return HelpMsg },
	}
)

func (h Handler) ShowIndexForPath(p string) ([]byte, error) {
	out := new(bytes.Buffer)

	pathEl := strings.Split(p, "/")
	pathEl[0] = "/"
	index := Index{
		Path:  path.Join(pathEl...),
		Files: make([]IndexEntry, 0),
	}

	base := os.DirFS(string(h.BasePath))
	fs.WalkDir(base, p, func(file string, d fs.DirEntry, err error) error {
		if err != nil {
			log.Printf("error: %s", err)
			return err
		}
		fi, err := d.Info()
		if err != nil {
			log.Printf("error: %s", err)
			return err
		}
		if fi.IsDir() {
			if p == file {
				return nil
			}
			ie := IndexEntry{parent: &index, Path: fi.Name()}
			keyPath := path.Join(file, KeyFileName)
			_, err := fs.Stat(base, keyPath)
			ie.HasSecret = !errors.Is(err, fs.ErrNotExist)

			contentPath := path.Join(file, ContentFileName)
			ci, err := fs.Stat(base, contentPath)
			if err != nil {
				log.Printf("error: %s", err)
				return nil
			}
			ie.Size = ci.Size()
			ie.ModTime = ci.ModTime()
			index.Files = append(index.Files, ie)
		}

		return nil
	})
	if len(index.Files) == 0 {
		return nil, errorRedirectToContent
	}

	helpers["title"] = func() template.HTMLAttr { return template.HTMLAttr(fmt.Sprintf("File index %s", index.Path)) }
	t := template.New("main.html").Funcs(helpers)
	if _, err := t.ParseFS(ass.TemplateFS, tplNames...); err != nil {
		return out.Bytes(), fmt.Errorf("unable to parse templates %v: %w", tplNames, err)
	}
	if err := t.Execute(out, &index); err != nil {
		return out.Bytes(), fmt.Errorf("unable to parse templates %v: %w", tplNames, err)
	}
	return out.Bytes(), nil
}

func (h Handler) ShowRequest(path, defTitle string) ([]byte, time.Time, error) {
	out := new(bytes.Buffer)
	content, err := h.BasePath.LoadPath(path)
	if err != nil {
		if !errors.Is(err, os.ErrNotExist) {
			return out.Bytes(), time.Time{}, fmt.Errorf("unable to load from path %q: %w", path, err)
		}
		log.Printf("unable to load %q, probably a new file", path)
	}
	key, _ := h.BasePath.LoadKeyForPath(path)
	modTime, _ := h.BasePath.ModTimePath(path)

	p := Page{
		Secret:   key,
		Path:     path,
		Modified: modTime,
		Content:  template.HTML(content),
	}

	helpers["title"] = p.Title(defTitle)
	helpers["help"] = p.Help()
	t := template.New("main.html").Funcs(helpers)
	if _, err := t.ParseFS(ass.TemplateFS, tplNames...); err != nil {
		return out.Bytes(), time.Time{}, fmt.Errorf("unable to parse templates %v: %w", tplNames, err)
	}
	if err := t.Execute(out, &p); err != nil {
		return out.Bytes(), time.Time{}, fmt.Errorf("unable to parse templates %v: %w", tplNames, err)
	}
	return out.Bytes(), modTime, nil
}

func RandomURL(r *http.Request) *url.URL {
	rPath := base64.RawURLEncoding.Strict().EncodeToString([]byte(fmt.Sprintf("%d", rnd.Uint32())))

	u := *r.URL
	u.Host = r.Host
	u.Path = path.Join(u.Path, rPath)
	u.RawQuery = ""

	return &u
}

func RedirectToRandom(w http.ResponseWriter, r *http.Request) {
	http.Redirect(w, r, RandomURL(r).String(), http.StatusTemporaryRedirect)
}

var day = 24 * time.Hour

func (h *Handler) Handle(w http.ResponseWriter, r *http.Request) {
	st := time.Now()
	defer func() {
		log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
	}()

	switch r.Method {
	case http.MethodPatch:
		key, err := h.CheckOrUpdateKeyRequest(r)
		if err != nil {
			writeError(w, err)
		}
		w.Header().Add("Authentication-Info", key)
		return
	case http.MethodDelete:
		if err := h.DeleteRequest(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodPost:
		if err := h.UpdateRequest(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodGet:
		if r.URL.Query().Has("random") {
			RedirectToRandom(w, r)
			return
		}

		p := getPathFromRequest(r)
		if len(r.URL.Path) > 1 && r.URL.Path[len(r.URL.Path)-1] == '/' {
			out, err := h.ShowIndexForPath(p)
			if err != nil {
				if errors.Is(err, errorRedirectToContent) {
					url := filepath.Clean(r.RequestURI)
					log.Printf("Redirecting to %s", url)
					http.Redirect(w, r, url, http.StatusTemporaryRedirect)
					return
				}
				writeError(w, err)
			}
			w.Write(out)
			return
		}

		out, mod, err := h.ShowRequest(p, cleanHost(r.Host))
		if err != nil {
			writeError(w, err)
		}
		if !mod.IsZero() {
			//w.Header().Set("Last-Modified", modTime.Format(time.RFC1123))
		}
		//w.Header().Set("Cache-Control", fmt.Sprintf("public, max-age=%d", int(day.Seconds())))
		w.Write(out)
		return
	}
	http.Error(w, "405 method not allowed", http.StatusMethodNotAllowed)
}

func getPathFromRequest(r *http.Request) string {
	host := r.URL.Host
	if len(host) == 0 {
		host = r.Host
	}
	return path.Join(host, r.URL.Path)
}

func getKeyFromRequest(r *http.Request) []byte {
	return []byte(r.Header.Get("Authorization"))
}

func New(storage string) Handler {
	return Handler{
		BasePath:      Storage(storage),
		authorization: make(map[string][]byte),
	}
}
