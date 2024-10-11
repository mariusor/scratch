//go:build prod || qa

package assets

import (
	"errors"
	"fmt"
	"io/fs"
	"mime"
	"net/http"
	"os"
	"path/filepath"
	"time"
)

var TemplateFS, _ = fs.Sub(FS, "templates")

func Write(s fs.FS, errFn func(http.ResponseWriter, *http.Request, ...error)) func(http.ResponseWriter, *http.Request) {
	const cacheTime = 8766 * time.Hour

	assetContents := make(map[string][]byte)
	mime.AddExtensionType(".ico", "image/vnd.microsoft.icon")
	mime.AddExtensionType(".txt", "text/plain; charset=utf-8")
	return func(w http.ResponseWriter, r *http.Request) {
		asset := r.RequestURI
		mimeType := mime.TypeByExtension(filepath.Ext(asset))

		buf, ok := assetContents[asset]
		if !ok {
			cont, err := fs.ReadFile(s, asset)
			if err != nil {
				if errors.Is(err, os.ErrNotExist) {
					err = fmt.Errorf("not found %s %w", asset, err)
				}
				errFn(w, r, err)
				return
			}
			buf = cont
		}
		assetContents[asset] = buf

		w.Header().Set("Cache-Control", fmt.Sprintf("public,max-age=%d", int(cacheTime.Seconds())))
		if mimeType != "" {
			w.Header().Set("Content-Type", mimeType)
		}
		w.Write(buf)
	}
}
