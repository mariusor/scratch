package scratch

import (
	"bytes"
	"encoding/base64"
	"encoding/json"
	"errors"
	"fmt"
	"html/template"
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
	BasePath storage
	Assets   assets.Maps
}

var unauthorizedErr = fmt.Errorf("missmatched key")

func (h Handler) DeleteRequest(r *http.Request) error {
	if err := r.ParseForm(); err != nil {
		return err
	}

	path := getPathFromRequest(r)
	if !h.BasePath.CheckKeyForPath(getKeyFromRequest(r), path) {
		return fmt.Errorf("unauthorized to delete path %q: %w", path, unauthorizedErr)
	}
	return h.BasePath.DeletePath(path)
}

func (h Handler) CheckKey(r *http.Request) bool {
	key := getKeyFromRequest(r)
	path := getPathFromRequest(r)

	k, _ := h.BasePath.LoadKeyForPath(path)
	return bytes.Equal(k, key)
}

func (h Handler) SaveKey(r *http.Request) error {
	path := getPathFromRequest(r)
	if !h.CheckKey(r) {
		return fmt.Errorf("unauthorized to update key %s: %w", path, unauthorizedErr)
	}
	key := r.PostFormValue("_")

	if len(key) > 0 {
		return h.BasePath.SaveKeyForPath([]byte(key), path)
	}
	return nil
}

func (h Handler) UpdateRequest(r *http.Request) error {
	path := getPathFromRequest(r)
	if !h.CheckKey(r) {
		return fmt.Errorf("unauthorized to update %s: %w", path, unauthorizedErr)
	}
	content := r.PostFormValue("_")
	log.Printf("saving %dbytes", len(content))
	if err := h.BasePath.SavePath(content, path); err != nil {
		return err
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

func (h Handler) CheckRequest(r *http.Request) error {
	key := getKeyFromRequest(r)
	path := getPathFromRequest(r)
	if !h.BasePath.CheckKeyForPath(key, path) {
		return fmt.Errorf("unauthorized %q: %w", path, unauthorizedErr)
	}
	return nil
}

func RandomURL(r *http.Request) *url.URL {
	rand := int16(rand.New(rand.NewSource(time.Now().UnixNano())).Uint32())
	rPath := base64.RawURLEncoding.Strict().EncodeToString([]byte(fmt.Sprintf("%d", rand)))

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

func (h Handler) Handle(w http.ResponseWriter, r *http.Request) {
	st := time.Now()
	var err error
	defer func() {
		log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
	}()

	switch r.Method {
	case http.MethodPatch:
		if err = h.SaveKey(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodDelete:
		if err = h.DeleteRequest(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodPost:
		if err = h.UpdateRequest(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodHead:
		if err := h.CheckRequest(r); err != nil {
			writeError(w, err)
		}
		return
	case http.MethodGet:
		if r.URL.Query().Has("random") {
			RedirectToRandom(w, r)
			return
		}
		out, modTime, err := h.ShowRequest(r)
		if err != nil {
			writeError(w, err)
		}
		if !modTime.IsZero() {
			//w.Header().Set("Last-Modified", modTime.Format(time.RFC1123))
		}
		//w.Header().Set("Cache-Control", fmt.Sprintf("public, max-age=%d", int(day.Seconds())))
		w.Write(out)
		return
	}
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
