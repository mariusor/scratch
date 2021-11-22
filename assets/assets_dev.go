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

type Path struct {
	p string
	i []string
}

func (p Path) Name() string {
	log.Printf("getting name for %s", p.p)
	return p.p
}

func (p Path) Size() int64 {
	var s int64 = 0
	log.Printf("asserting size for %s", p.p)
	for _, file := range p.i {
		f, _ := os.Stat(file)
		s += f.Size()
	}
	log.Printf("size is %d", s)
	return s
}

func (p Path) Mode() fs.FileMode {
	var m fs.FileMode
	for _, file := range p.i {
		f, _ := os.Stat(file)
		m = f.Mode()
	}
	return m
}

func (p Path) ModTime() time.Time {
	return time.Now().UTC()
}

func (p Path) IsDir() bool {
	return false
}

func (p Path) Sys() interface{} {
	return nil
}

func (p Path) Stat() (fs.FileInfo, error) {
	return p, nil
}

func (p Path) Read(buf []byte) (int, error) {
	log.Printf("reading %s: %v", p.p, p.i)
	var err error
	for _, file := range p.i {
		t, err := os.ReadFile(file)
		if err != nil {
			err = fmt.Errorf("error reading %s: %w", file, err)
			break
		}
		buf = append(buf, t...)
		log.Printf("read %s: %db", file, len(t))
	}
	return len(buf), err
}

func (p Path) Close() error {
	return nil
}

func (a Maps) Open(name string) (fs.File, error) {
	i, ok := a[name]
	if !ok {
		return nil, fs.ErrNotExist
	}
	log.Printf("opening %s %v", name, i)
	return Path{p: name, i: i}, nil
}

func (a Maps) ReadFile(name string) ([]byte, error) {
	var err error
	buf := make([]byte, 0)
	for asset, m := range a {
		if name != asset {
			continue
		}
		for _, file := range m {
			t, err := os.ReadFile(file)
			if err != nil {
				err = fmt.Errorf("error reading %s: %w", file, err)
				break
			}
			buf = append(buf, t...)
			log.Printf("read %s: %db", file, len(t))
		}
	}
	return buf, err
}

func writeAsset(s Maps) func(http.ResponseWriter, *http.Request) {
	return func(w http.ResponseWriter, r *http.Request) {
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

