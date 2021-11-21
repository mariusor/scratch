package scratch

import (
	"bytes"
	"errors"
	"fmt"
	"html/template"
	"log"
	"net/http"
	"os"
	"time"

	"git.sr.ht/~mariusor/scratch/assets"
)

type Page struct {
	Secret   []byte
	Path     string
	Created  time.Time
	Modified time.Time
	Content  template.HTML
}

type Handler struct {
	BasePath storage
}

const HelpMsg = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image"

var unauthorizedErr = fmt.Errorf("missmatched key")

func (h Handler) DeleteRequest(r *http.Request) error {
	if err := r.ParseForm(); err != nil {
		return err
	}

	path := r.URL.Path
	if !h.BasePath.CheckKeyForPath(getKeyFromRequest(r), path) {
		return fmt.Errorf("unauthorized to save to %q: %w", path, unauthorizedErr)
	}
	return h.BasePath.DeletePath(path)
}

func (h Handler) UpdateRequest(r *http.Request) error {
	if err := r.ParseForm(); err != nil {
		return err
	}
	path := r.URL.Path
	ff := r.PostForm
	key := getKeyFromRequest(r)
	action := ff.Get("action")
	switch action {
	case "check":
		log.Printf("checking: %s", key)
		if h.BasePath.CheckKeyForPath(key, path) {
			//
		}
		delete(ff, "key")
	case "save":
		content := ff.Get("content")
		log.Printf("saving: %s", content)
		if h.BasePath.CheckKeyForPath(key, path) {
			if err := h.BasePath.SavePath(content, path); err != nil {
				return err
			}
		} else {
			return fmt.Errorf("unauthorized to save to %q: %w", path, unauthorizedErr)
		}
		delete(ff, "content")
	default:
		log.Printf("unknown action")
	}
	delete(ff, "action")
	log.Printf("%s", ff)
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

func (h Handler) ShowRequest(r *http.Request) ([]byte, error) {
	out := new(bytes.Buffer)
	templates := assets.Maps{
		"main.html": {"./templates/main.html"},
	}
	t := template.New("main.html").Funcs(template.FuncMap{
		"help": func() template.HTMLAttr { return HelpMsg },
	})
	if _, err := t.ParseFS(templates, templates.Names()...); err != nil {
		return out.Bytes(), fmt.Errorf("unable to parse templates %v: %w", templates.Names(), err)
	}
	path := r.URL.Path
	content, err := h.BasePath.LoadPath(path)
	if err != nil {
		if !errors.Is(err, os.ErrNotExist) {
			return out.Bytes(), fmt.Errorf("unable to load from path %q: %w", path, err)
		}
		log.Printf("unable to load %q, probably a new file", path)
	}
	p := Page{
		Secret:  nil,
		Path:    path,
		Content: template.HTML(content),
	}
	if err := t.Execute(out, &p); err != nil {
		return out.Bytes(), fmt.Errorf("unable to parse templates %v: %w", templates.Names(), err)
	}
	return out.Bytes(), nil
}

func (h Handler) Handle(w http.ResponseWriter, r *http.Request) {
	st := time.Now()
	defer func() {
		log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
	}()

	switch r.Method {
	case http.MethodDelete:
		if err := h.DeleteRequest(r); err != nil {
			writeError(w, err)
			return
		}
		return
	case http.MethodPost:
		if err := h.UpdateRequest(r); err != nil {
			writeError(w, err)
			return
		}
		return
	case http.MethodHead:
		if _, err := h.ShowRequest(r); err != nil {
			w.WriteHeader(statusError(err))
		}
		return
	case http.MethodGet:
		out, err := h.ShowRequest(r)
		if err != nil {
			writeError(w, err)
		}
		w.Write(out)
		return
	}
	return
}

func getKeyFromRequest(r *http.Request) []byte {
	if err := r.ParseForm(); err != nil {
		return nil
	}
	key := r.PostForm.Get("key")
	if len(key) == 0 {
		return nil
	}
	return []byte(key)
}
