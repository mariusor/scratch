# Scratch

This project can be used to host an online HTML scratch pad.

If you ever missed a "virtual" piece of paper to write a quick idea and have it available from anywhere else with an internet connection, well, this can be it.

It allows you to claim any free page on a it's domain and edit it, saving is done automatically.

You can protect it with a password using the icon in the upper-right corner.

You can also group similar notes under a name, like `~groceries`, `~research`. To create a note under it, just access `http://domain.tld/~examplenamehere/notenamehere`. e.g. `http://domain.tld/~groceries/for-next-week`.

To access all the notes under a name, simply access the url without a note name. e.g. `http://domain.tld/~groceries/`. The slash at the end is important here.

## Run

Make sure you have Go and GNU Make installed. Then do:

```sh
$ make run
```

It should be available at `http://localhost:8097`
