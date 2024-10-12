//go:build !(prod || qa)

package assets

import (
	"git.sr.ht/~mariusor/assets"
	"io/fs"
	"os"
	"path/filepath"
)

var (
	rootPath, _ = filepath.Abs("./")
	rootFS      = os.DirFS(rootPath)
	assetFS, _  = fs.Sub(rootFS, "static")
	FS          = assets.Aggregate(assetFS, rootFS)

	TemplateFS, _ = fs.Sub(assetFS, "templates")
)
