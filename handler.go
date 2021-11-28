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
	"time"

	"git.sr.ht/~mariusor/scratch/assets"
)

type Handler struct {
	BasePath      storage
	Assets        assets.Maps
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
			log.Printf("Saving pw for %s: %q", path, pw)
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

func (h Handler) ShowRequest(r *http.Request) ([]byte, time.Time, error) {
	out := new(bytes.Buffer)

	path := getPathFromRequest(r)
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

	templates := assets.WithPrefix("static/templates", assets.Maps{
		"main.html": {"main.html"},
	})
	t := template.New("main.html").Funcs(template.FuncMap{
		"style":  h.Assets.StyleNode,
		"script": h.Assets.JsNode,
		"csrf":   jsCSRF,
		"title":  p.Title(r),
		"help":   p.Help(r),
	})
	if _, err := t.ParseFS(templates, templates.Names()...); err != nil {
		return out.Bytes(), time.Time{}, fmt.Errorf("unable to parse templates %v: %w", templates.Names(), err)
	}
	if err := t.Execute(out, &p); err != nil {
		return out.Bytes(), time.Time{}, fmt.Errorf("unable to parse templates %v: %w", templates.Names(), err)
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
		out, mod, err := h.ShowRequest(r)
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
	return path.Join(r.URL.Host, r.URL.Path)
}

func getKeyFromRequest(r *http.Request) []byte {
	return []byte(r.Header.Get("Authorization"))
}

func New(storage string, files assets.Maps) Handler {
	return Handler{
		BasePath:      Storage(storage),
		Assets:        files,
		authorization: make(map[string][]byte),
	}
}
