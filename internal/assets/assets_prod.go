//go:build prod || qa

package assets

import "io/fs"

var TemplateFS, _ = fs.Sub(FS, "templates")
