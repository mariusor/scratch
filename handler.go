package scratch

import (
	"bytes"
	"html/template"
	"log"
	"net/http"
	"os"
	"time"
)

type Page struct {
	Secret   []byte
	Path     string
	Created  time.Time
	Modified time.Time
	Content  template.HTML
}

const HelpMsg = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image"

func Handler(w http.ResponseWriter, r *http.Request) {
	st := time.Now()
	defer func() {
		log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
	}()

	if r.Method == http.MethodPost || r.Method == http.MethodDelete {
		out := make([]byte, 0)

		defer r.Body.Close()
		if _, err := r.Body.Read(out); err != nil {
			log.Printf("Error: %s", err)
			w.WriteHeader(http.StatusInternalServerError)
			w.Write([]byte(err.Error()))
			return
		}
		log.Printf("%s", out)
	}
	if r.Method == http.MethodGet || r.Method == http.MethodHead {
		out := new(bytes.Buffer)
		t := template.New("main.html").Funcs(template.FuncMap{
			"help": func() template.HTMLAttr {
				return template.HTMLAttr(HelpMsg)
			},
		})
		if _, err := t.ParseFS(os.DirFS("templates"), "main.html"); err != nil {
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
	}
}
