SHELL := bash
.ONESHELL:
.SHELLFLAGS := -eu -o pipefail -c
.DELETE_ON_ERROR:

PROJECT_NAME := $(shell basename $(PWD))
ENV ?= dev
LDFLAGS ?= -X main.version=$(VERSION)
BUILDFLAGS ?= -trimpath -a -ldflags '$(LDFLAGS)'
TEST_FLAGS ?= -count=1

GO := go
APPSOURCES := $(wildcard *.go cmd/*/*.go assets/*.go)
ASSETFILES := $(wildcard static/*/* static/*)

export CGO_ENABLED=0
export VERSION=(unknown)

ifneq ($(ENV), dev)
	LDFLAGS += -s -w -extldflags "-static"
	APPSOURCES += internal/assets/assets.gen.go

assets: internal/assets/assets.gen.go
else:
assets:
endif

ifeq ($(shell git describe --always > /dev/null 2>&1 ; echo $$?), 0)
export VERSION = $(shell git describe --always --dirty=-git)
endif
ifeq ($(shell git describe --tags > /dev/null 2>&1 ; echo $$?), 0)
export VERSION = $(shell git describe --tags)
endif

BUILD := $(GO) build $(BUILDFLAGS)
TEST := $(GO) test $(BUILDFLAGS)

.PHONY: all run clean test assets download

all: scratch

download:
	$(GO) mod tidy

internal/assets/assets.gen.go: $(ASSETFILES)
	go generate -tags $(ENV) ./internal/assets.go

scratch: bin/scratch
bin/scratch: download go.mod cmd/scratch/main.go $(APPSOURCES)
	$(BUILD) -tags $(ENV) -o $@ ./cmd/scratch/main.go

run: scratch
	@./bin/scratch

clean:
	-$(RM) bin/* internal/assets/assets.gen.go

test: TEST_TARGET := ./{app,internal}/...
test:
	$(TEST) $(TEST_FLAGS) $(TEST_TARGET)

coverage: TEST_TARGET := .
coverage: TEST_FLAGS += -covermode=count -coverprofile $(PROJECT_NAME).coverprofile
coverage: test
