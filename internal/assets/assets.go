package assets

import (
	"bufio"
	"bytes"
	"crypto/sha256"
	"encoding/base64"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"path"
	"path/filepath"
	"time"
)

const (
	year = 8766 * time.Hour
)

type (
	Maps     map[string][]string
	Contents map[string][]byte
)

func (a Maps) ReadAll(name string) ([]byte, error) {
	files, ok := a[name]
	if !ok {
		return nil, fs.ErrNotExist
	}
	buf := bytes.Buffer{}
	for _, file := range files {
		if piece, _ := getFileContent(assetPath(file)); len(piece) > 0 {
			buf.Write(piece)
		}
	}
	return buf.Bytes(), nil
}

func (a Maps) Names() []string {
	names := make([]string, 0)
	for n := range a {
		names = append(names, n)
	}
	return names
}

func (a Maps) Open(name string) (fs.File, error) {
	names, _ := a[name]
	return openFsFn(names[0])
}

func (a Maps) Routes(m *http.ServeMux) error {
	for asset := range a {
		if !path.IsAbs(asset) {
			return fmt.Errorf("Asset path %q needs to be absolute", asset)
		}
		m.HandleFunc(asset, ServeAsset(a))
	}
	return nil
}

func (a Maps) SubresourceIntegrityHash(name string) (string, bool) {
	files, ok := a[name]
	if !ok {
		return "", false
	}
	buf := new(bytes.Buffer)
	for _, asset := range files {
		ext := path.Ext(name)
		if len(ext) <= 1 {
			continue
		}
		dat, err := getFileContent(assetPath(ext[1:], asset))
		if err != nil {
			continue
		}
		buf.Write(dat)
	}
	b := buf.Bytes()
	if len(b) == 0 {
		return "", false
	}
	return sha(b), true
}

// GetFullFile
func GetFullFile(name string) ([]byte, error) {
	return getFileContent(name)
}

/*
// TemplateNames returns asset names necessary for unrolled.Render
func TemplateNames() []string {
	names := make([]string, 0)
	walkFsFn(templateDir, func(path string, info os.FileInfo, err error) error {
		if info != nil && !info.IsDir() {
			names = append(names, path)
		}
		return nil
	})
	return names
}
*/

func getFileContent(name string) ([]byte, error) {
	f, err := openFsFn(name)
	if err != nil {
		return nil, err
	}
	defer f.Close()
	r := bufio.NewReader(f)
	b := new(bytes.Buffer)

	if _, err = r.WriteTo(b); err != nil {
		return nil, err
	}
	return b.Bytes(), nil
}

func assetPath(pieces ...string) string {
	return path.Clean(path.Join(pieces...))
}

// Svg returns an svg by path for display inside templates
func Svg(name string) template.HTML {
	return Asset()(name)
}

// Style returns a style by path for displaying inline
func Style(name string) template.CSS {
	return template.CSS(Asset()("css/" + name))
}

// Svg returns an svg by path for displaying inline
func Js(name string) template.HTML {
	return Asset()("js/" + name)
}

// Template returns an asset by path for unrolled.Render
func Template(name string) ([]byte, error) {
	return getFileContent(name)
}

func sha(d []byte) string {
	sha := sha256.Sum256(d)
	return base64.StdEncoding.EncodeToString(sha[:])
}

func AssetSha(name string) string {
	dat, err := getFileContent(assetPath(name))
	if err != nil || len(dat) == 0 {
		return ""
	}
	return sha(dat)
}

// Integrity gives us the integrity attribute for Subresource Integrity
func Integrity(name string) template.HTMLAttr {
	return template.HTMLAttr(fmt.Sprintf(` identity="sha256-%s"`, AssetSha(name)))
}

func ServeAsset(s Maps) func(w http.ResponseWriter, r *http.Request) {
	return writeAsset(s)
}

func ServeStatic(st string) func(w http.ResponseWriter, r *http.Request) {
	return func(w http.ResponseWriter, r *http.Request) {
		fullPath := filepath.Clean(filepath.Join(st, r.URL.Path))
		w.Header().Set("Cache-Control", fmt.Sprintf("public, max-age=%d, immutable", int(year.Seconds())))
		http.ServeFile(w, r, fullPath)
	}
}

// Asset returns an asset by path for display inside templates
// it is mainly used for rendering the svg icons file
var Asset = assetLoad
