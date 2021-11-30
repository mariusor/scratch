package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"syscall"
	"time"

	"git.sr.ht/~mariusor/scratch"
	"git.sr.ht/~mariusor/scratch/assets"
	w "git.sr.ht/~mariusor/wrapper"
)

type config struct {
	Secure      bool
	StoragePath string
	KeyPath     string
	CertPath    string
	Listen      string
	TimeOut     time.Duration
}

var assetFiles = assets.WithPrefix("static", assets.Maps{
	"/main.js":     {"js/jquery.js", "js/jquery.editable.js", "js/default.js", "js/dragndrop.js"},
	"/index.js":    {"js/index.js"},
	"/main.css":    {"css/reset.css", "css/style.css"},
	"/index.css":   {"css/reset.css", "css/style.css", "css/index.css"},
	"/robots.txt":  {"robots.txt"},
	"/favicon.ico": {"favicon.ico"},
	"/icons.svg":   {"icons.svg"},
})

func main() {
	wd, err := os.Getwd()
	if err != nil {
		log.Panicf("Error: %s", err)
	}

	conf := config{
		Listen:      "localhost:8097",
		StoragePath: wd,
	}

	ctx, cancelFn := context.WithTimeout(context.TODO(), conf.TimeOut)

	mux := http.NewServeMux()
	if err := assets.Routes(mux, assetFiles); err != nil {
		log.Panicf("Error: %s", err)
	}
	h := scratch.New(conf.StoragePath, assetFiles)
	mux.HandleFunc("/", h.Handle)

	listenOn := "HTTP"
	setters := []w.SetFn{w.Handler(mux)}
	if conf.Secure && len(conf.CertPath)+len(conf.KeyPath) > 0 {
		listenOn = "HTTPS"
		setters = append(setters, w.HTTPS(conf.Listen, conf.CertPath, conf.KeyPath))
	} else {
		setters = append(setters, w.HTTP(conf.Listen))
	}
	log.Printf("Listening on %s %s", listenOn, conf.Listen)

	runFn, stopFn := w.HttpServer(setters...)

	defer func() {
		if err := stopFn(ctx); err != nil {
			log.Printf("Err: %s", err)
		}
		cancelFn()
	}()

	exit := w.RegisterSignalHandlers(w.SignalHandlers{
		syscall.SIGHUP: func(_ chan int) {
			log.Printf("SIGHUP received, reloading configuration")
		},
		syscall.SIGINT: func(exit chan int) {
			log.Printf("SIGINT received, stopping")
			exit <- 0
		},
		syscall.SIGTERM: func(exit chan int) {
			log.Printf("SIGITERM received, force stopping")
			exit <- 0
		},
		syscall.SIGQUIT: func(exit chan int) {
			log.Printf("SIGQUIT received, force stopping with core-dump")
			exit <- 0
		},
	}).Exec(func() error {
		if err := runFn(); err != nil {
			log.Printf("Error: %s", err)
			return err
		}
		var err error
		// Doesn't block if no connections, but will otherwise wait until the timeout deadline.
		go func(e error) {
			log.Printf("Error: %s", err)
		}(err)
		return err
	})
	if exit == 0 {
		log.Printf("Shutting down")
	}
	os.Exit(exit)
}
