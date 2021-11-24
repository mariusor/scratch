//go:build prod
// +build prod

package assets

import (
	"fmt"
	"io/fs"
	"time"
)

type Contents map[string][]byte

func (p Path) Mode() fs.FileMode {
	var m fs.FileMode
	for _, file := range p.i {
		f, _ := assets.Stat(file)
		m = f.Mode()
	}
	return m
}

func (p Path) ModTime() time.Time {
	var m time.Time
	for _, file := range p.i {
		f, _ := assets.Stat(file)
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
	asset, ok := a[name]
	if !ok {
		return nil, fmt.Errorf("asset group does not exist %s: %w", name, fs.ErrNotExist)
	}
	return Path{p: name, i: asset}, nil
}

func (a Maps) ReadFile(name string) ([]byte, error) {
	var err error
	asset, ok := a[name]
	if !ok {
		return nil, fmt.Errorf("asset does not exist in current group %s: %w", name, fs.ErrNotExist)
	}
	buf := make([]byte, 0)
	for _, file := range asset {
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
	}
	return buf, err
}
