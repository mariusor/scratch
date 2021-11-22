//go:build prod
// +build prod

package assets

import (
	"fmt"
	"io/fs"
	"log"
	"mime"
	"net/http"
	"path"
	"path/filepath"
)

type Contents map[string][]byte

func (a Maps) Open(name string) (fs.File, error) {
	return assets.Open(name)
}

func (a Maps) ReadFile(name string) ([]byte, error) {
	var err error
	buf := make([]byte, 0)
	for asset, m := range a {
		if name != asset {
			continue
		}
		for _, file := range m {
			f, err := assets.Open(file)
			if err != nil {
				err = fmt.Errorf("error reading %s: %w", file, err)
				continue
			}
			fi, err := f.Stat()
			if err != nil {
				err = fmt.Errorf("error reading %s: %w", file, err)
				continue
			}
			t := make([]byte, fi.Size())

			if _, err := f.Read(t); err != nil {
				err = fmt.Errorf("error reading %s: %w", file, err)
				continue
			}
			buf = append(buf, t...)
			log.Printf("read %s: %db", file, len(t))
		}
	}
	return buf, err
}

func writeAsset(s Maps) func(http.ResponseWriter, *http.Request) {
	assetContents := make(Contents)
	return func(w http.ResponseWriter, r *http.Request) {
		asset := filepath.Clean(r.URL.Path)
		ext := path.Ext(r.RequestURI)
		mimeType := mime.TypeByExtension(ext)
		cont, ok := assetContents[asset]
		if !ok {
			var err error
			cont, err = s.ReadFile(asset)
			if err != nil {
				w.Write([]byte("not found"))
				w.WriteHeader(http.StatusNotFound)
				return
			}
			assetContents[asset] = cont
		}

		w.Header().Set("Cache-Control", fmt.Sprintf("public,max-age=%d", int(year.Seconds())))
		w.Header().Set("Content-Type", mimeType)
		w.Write(cont)
	}
}
