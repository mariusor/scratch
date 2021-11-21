package scratch

import (
	"bytes"
	"html/template"
	"log"
	"net/http"
	"time"

	"git.sr.ht/~mariusor/scratch/internal/assets"
)

type Page struct {
	Secret   []byte
	Path     string
	Created  time.Time
	Modified time.Time
	Content  template.HTML
}

const HelpMsg = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image"

func UpdateCurrentPath(r *http.Request) error {
	//log.Printf("%#v", r.Header)
	ff := r.Form
	log.Printf("%s", ff)
	return nil
}

func Handle(w http.ResponseWriter, r *http.Request) {
	st := time.Now()
	defer func() {
		log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
	}()

	if r.Method == http.MethodPost || r.Method == http.MethodDelete {
		if err := UpdateCurrentPath(r); err != nil {
			log.Printf("Error: %s", err)
			w.WriteHeader(http.StatusInternalServerError)
			w.Write([]byte(err.Error()))
		}
		return
	}
	if r.Method == http.MethodGet || r.Method == http.MethodHead {
		out := new(bytes.Buffer)
		templates := assets.Maps{
			"main.html": {"./templates/main.html"},
		}
		t := template.New("main.html").Funcs(template.FuncMap{
			"help": func() template.HTMLAttr {
				return template.HTMLAttr(HelpMsg)
			},
		})
		if _, err := t.ParseFS(templates, templates.Names()...); err != nil {
			log.Printf("Error: %s", err)
			w.WriteHeader(http.StatusInternalServerError)
			w.Write([]byte(err.Error()))
			return
		}
		if err := t.Execute(out, &Page{}); err != nil {
			log.Printf("Error: %s", err)
			w.WriteHeader(http.StatusInternalServerError)
			w.Write([]byte(err.Error()))
			return
		}
		w.Write(out.Bytes())
		return
	}
	http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
}
