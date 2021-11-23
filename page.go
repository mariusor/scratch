package scratch

import (
	"bytes"
	"github.com/PuerkitoBio/goquery"
	"html/template"
	"net/http"
	"strings"
	"time"
)

const HelpMsg = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image"

type Page struct {
	Secret   []byte
	Path     string
	Created  time.Time
	Modified time.Time
	Content  template.HTML
}

func cleanHost (host string) string {
	if pos := strings.LastIndex(host, ":"); pos >= 0 {
		host = strings.TrimRight(host, host[pos:])
	}
	return host
}

func (p Page) Help(_ *http.Request) func() template.HTMLAttr {
	return func () template.HTMLAttr {
		return HelpMsg
	}
}
func (p Page) Title(r *http.Request) func() template.HTML {
	host := cleanHost(r.Host)
	subtitle := "Empty page"
	if len(p.Content) > 0 {
		subtitle = "Online scratchpad for your convenience"
		doc, _ := goquery.NewDocumentFromReader(bytes.NewReader([]byte(p.Content)))
		if doc != nil {
			if titleSel := doc.Find("h1"); titleSel.Size() > 0 {
				subtitle = titleSel.Text()
			}
		}
	}
	title := host + ": " + subtitle
	if len(p.Secret) > 0 {
		title = "🔒 " + title
	}
	return func() template.HTML {
		return template.HTML(title)
	}
}
