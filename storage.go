package scratch

import (
	"bytes"
	"fmt"
	"os"
	"path"
	"time"

	"github.com/pkg/errors"
)

const (
	StorageDirName  = "cache"
	KeyFileName     = "key.bin"
	ContentFileName = "content"
)

type storage string

func Storage(base string) storage {
	return storage(path.Join(base, StorageDirName))
}

func (s storage) CheckKeyForPath(key []byte, to string) bool {
	k, err := s.LoadKeyForPath(to)
	if err != nil && len(key) > 0 {
		return false
	}
	return bytes.Equal(key, k)
}

func (s storage) SaveKeyForPath(key []byte, to string) error {
	to = path.Join(string(s), to, KeyFileName)
	return writeToPath(to, key)
}

func (s storage) LoadKeyForPath(from string) ([]byte, error) {
	from = path.Join(string(s), from, KeyFileName)
	return loadFromPath(from)
}

func (s storage) SavePath(content, to string) error {
	to = path.Join(string(s), to, ContentFileName)
	return writeToPath(to, []byte(content))
}

func (s storage) DeletePath(what string) error {
	content := path.Join(string(s), what, ContentFileName)
	if err := os.RemoveAll(content); err != nil {
		return err
	}
	key := path.Join(string(s), what, KeyFileName)
	if err := os.RemoveAll(key); err != nil {
		return err
	}
	return nil
}

func (s storage) LoadPath(from string) (string, error) {
	from = path.Join(string(s), from, ContentFileName)
	content, err := loadFromPath(from)
	if err != nil {
		return "", err
	}
	return string(content), nil
}

func (s storage) ModTimePath(from string) (time.Time, error) {
	from = path.Join(string(s), from, ContentFileName)
	fi, err := os.Stat(from)
	if err != nil {
		return (time.Time{}).UTC(), err
	}

	return fi.ModTime().UTC(), nil
}

func mkDirIfNotExists(p string) error {
	fi, err := os.Stat(p)
	if err != nil && os.IsNotExist(err) {
		err = os.MkdirAll(p, os.ModeDir|os.ModePerm|0700)
	}
	if err != nil {
		return err
	}
	fi, err = os.Stat(p)
	if err != nil {
		return err
	} else if !fi.IsDir() {
		return errors.Errorf("path exists, and is not a folder %q", p)
	}
	return nil
}

func createOrOpenFile(p string) (*os.File, error) {
	if err := mkDirIfNotExists(path.Dir(p)); err != nil {
		return nil, err
	}
	return os.OpenFile(p, os.O_RDWR|os.O_CREATE|os.O_TRUNC, 0600)
}

func writeToPath(to string, content []byte) error {
	f, err := createOrOpenFile(to)
	if err != nil {
		return err
	}
	defer f.Close()

	content, err = encodeFn(content)
	if err != nil {
		return fmt.Errorf("could not marshal metadata: %w", err)
	}
	var wrote int
	wrote, err = f.Write(content)
	if err != nil {
		return fmt.Errorf("could not store encoded object: %w", err)
	}
	if wrote != len(content) {
		return fmt.Errorf("failed writing full object: %w", err)
	}
	return nil
}

func encodeFn(content []byte) ([]byte, error) {
	return content, nil
}

var decodeFn = encodeFn

func loadFromPath(from string) ([]byte, error) {
	content, err := os.ReadFile(from)
	if err != nil {
		return nil, fmt.Errorf("could not read from path %q: %w", from, err)
	}

	content, err = decodeFn(content)
	if err != nil {
		return nil, fmt.Errorf("could not marshal metadata: %w", err)
	}
	return content, nil
}
