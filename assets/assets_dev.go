//go:build !prod
// +build !prod

package assets

import (
	"fmt"
	"io/fs"
	"os"
	"time"
)

func (p Path) Mode() fs.FileMode {
	var m fs.FileMode
	for _, file := range p.i {
		f, err := os.Stat(file)
		if err != nil {
			continue
		}
		m = f.Mode()
	}
	return m
}

func (p Path) ModTime() time.Time {
	var m time.Time
	for _, file := range p.i {
		f, err := os.Stat(file)
		if err != nil {
			continue
		}
		if fm := f.ModTime(); fm.Sub(m) > 0 {
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
