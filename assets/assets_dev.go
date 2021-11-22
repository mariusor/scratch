//go:build !prod
// +build !prod

package assets

import (
	"errors"
	"fmt"
	"io/fs"
	"log"
	"mime"
	"net/http"
	"os"
	"path"
	"path/filepath"
	"time"
)


func (p Path) Mode() fs.FileMode {
	var m fs.FileMode
	for _, file := range p.i {
		f, _ := os.Stat(file)
		m = f.Mode()
	}
	return m
}

func (p Path) ModTime() time.Time {
	var m time.Time
	for _, file := range p.i {
		f, _ := os.Stat(file)
		if fm := f.ModTime(); fm.Sub(m) < 0 {
			m = fm
		}
	}
	return m
}

func (p Path) IsDir() bool {
	return false
}

func (a Maps) Open(name string) (fs.File, error) {
	i, ok := a[name]
	if !ok {
		return nil, fs.ErrNotExist
	}
	return Path{p: name, i: i}, nil
}

func (a Maps) ReadFile(name string) ([]byte, error) {
	var err error
	assets, ok := a[name]
	if !ok {
		return nil, fmt.Errorf("asset does not exist in current group %s: %w", name, fs.ErrNotExist)
	}
	buf := make([]byte, 0)
	for _, file := range assets {
		t, err := os.ReadFile(file)
		if err != nil {
			err = fmt.Errorf("error reading %s: %w", file, err)
			break
		}
		buf = append(buf, t...)
	}
	return buf, err
}

func writeAsset(s Maps) func(http.ResponseWriter, *http.Request) {
	return func(w http.ResponseWriter, r *http.Request) {
		st := time.Now()
		defer func() {
			log.Printf("[%s] %s %s", r.Method, r.URL.Path, time.Now().Sub(st).String())
		}()

		asset := filepath.Clean(r.URL.Path)
		if asset == "." {
			_, asset = filepath.Split(r.RequestURI)
		}
		mimeType := mime.TypeByExtension(path.Ext(r.RequestURI))

		buf, err := s.ReadFile(asset)
		if err != nil && errors.Is(err, fs.ErrNotExist) {
			w.Write([]byte(asset))
			w.Write([]byte(" not found"))
			w.WriteHeader(http.StatusNotFound)
			return
		}

		w.Header().Set("Cache-Control", fmt.Sprintf("public,max-age=%d", int(year.Seconds())))
		w.Header().Set("Content-Type", mimeType)
		w.Write(buf)
	}
}

