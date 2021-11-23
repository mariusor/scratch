package assets

import (
	"crypto/sha256"
	"encoding/base64"
	"fmt"
	"html/template"
	"io/fs"
	"net/http"
	"os"
	"path"
	"path/filepath"
	"time"
)

const (
	year = 8766 * time.Hour
)

type Maps map[string][]string

type Path struct {
	p string
	i []string
}

func (p Path) Name() string {
	return p.p
}

func (p Path) Size() int64 {
	var s int64 = 0
	for _, file := range p.i {
		f, _ := os.Stat(file)
		s += f.Size()
	}
	return s
}

func (p Path) Sys() interface{} {
	return nil
}

func (p Path) Stat() (fs.FileInfo, error) {
	return p, nil
}

func (p Path) Read(buf []byte) (int, error) {
	var err error
	for _, file := range p.i {
		t, err := os.ReadFile(file)
		if err != nil {
			err = fmt.Errorf("error reading %s: %w", file, err)
			break
		}
		buf = append(buf, t...)
	}
	return len(buf), err
}

func (p Path) Close() error {
	return nil
}

func (a Maps) Names() []string {
	names := make([]string, 0)
	for n := range a {
		names = append(names, n)
	}
	return names
}

func WithPrefix(prefix string, assetMap Maps) Maps {
	newMap := make(Maps)
	for k, assets := range assetMap {
		newAssets := make([]string, len(assets))
		for i, asset := range assets {
			newAssets[i] = path.Join(prefix, asset)
		}
		newMap[k] = newAssets
	}
	return newMap
}

func Routes(m *http.ServeMux, a Maps) error {
	for asset := range a {
		if !path.IsAbs(asset) {
			return fmt.Errorf("asset path %q needs to be absolute", asset)
		}
		m.HandleFunc(asset, ServeAsset(a))
	}
	return nil
}

func (a Maps) SubresourceIntegrityHash(name string) (string, bool) {
	dat, err := a.ReadFile(assetPath(name))
	if err != nil {
		return "", false
	}
	return sha(dat), true
}

/*
// GetFullFile
func GetFullFile(name string) ([]byte, error) {
	return a.getFileContent(name)
}

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

func assetPath(pieces ...string) string {
	return path.Clean(path.Join(pieces...))
}

// Svg returns an svg by path for display inside templates
func (a Maps) Svg(name string) template.HTML {
	return Asset(a)(name)
}

// StyleNode returns a style link for displaying inline
func (a Maps) StyleNode(name string) template.HTML {
	if !path.IsAbs(name) {
		name = path.Join("/", name)
	}
	link := fmt.Sprintf(`<link rel="stylesheet" href="%s"%s/>`, name, a.Integrity(name))
	return template.HTML(link)
}

// Style returns a style by path for displaying inline
func (a Maps) Style(name string) template.CSS {
	return template.CSS(Asset(a)(name))
}

// JsNode returns an javascript node by path for displaying inline
func (a Maps) JsNode(name string) template.HTML {
	if !path.IsAbs(name) {
		name = path.Join("/", name)
	}
	link := fmt.Sprintf(`<script src="%s" async%s></script>`, name, a.Integrity(name))
	return template.HTML(link)
}

// Js returns an svg by path for displaying inline
func (a Maps) Js(name string) template.HTML {
	return Asset(a)(name)
}

// Template returns an asset by path for unrolled.Render
func (a Maps) Template(name string) ([]byte, error) {
	return a.ReadFile(name)
}

func sha(d []byte) string {
	sha := sha256.Sum256(d)
	return base64.StdEncoding.EncodeToString(sha[:])
}

func (a Maps) AssetSha(name string) string {
	dat, err := a.ReadFile(assetPath(name))
	if err != nil || len(dat) == 0 {
		return ""
	}
	return sha(dat)
}

// Integrity gives us the integrity attribute for Subresource Integrity
func (a Maps) Integrity(name string) template.HTMLAttr {
	hash := a.AssetSha(name)
	if len(hash) == 0 {
		return ""
	}
	return template.HTMLAttr(fmt.Sprintf(` integrity="sha256-%s"`, hash))
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
func Asset (s Maps) func(string) template.HTML {
	return func(name string) template.HTML {
		b, _ := s.ReadFile(assetPath(name))
		return template.HTML(b)
	}
}
