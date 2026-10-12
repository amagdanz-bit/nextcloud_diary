# Diary

A (currently) very simple diary for Nextcloud

## Requirements

* PHP 8.3 or newer
* Nextcloud 30 – 35

## Templates

An empty entry offers a "Use template" menu next to the date. A template inserts a Markdown skeleton (e.g. a few
questions as headings) into the editor; after that it is a normal entry. Templates are defined in `src/templates.js`,
add an object with `id`, `name` and Markdown `content` there and rebuild the app to offer more.

## Installing on your own server

1. Build the app with `make` (needs PHP 8.3+, Composer and Node/npm). This installs the PHP libraries into `vendor/`
   and builds the frontend into `js/`.
2. Copy the whole `diary` folder (including `vendor/` and `js/`, `node_modules/` is not needed) into the `apps/`
   (or `custom_apps/`) folder of your Nextcloud.
3. Enable it with `occ app:enable diary` or on the apps page of your Nextcloud.

## Building Locally

1. Install PHP 8.3 (or newer) as well as the `xml`, `mbstring` and `gd` extensions e.g. with
   `sudo apt install php8.3 php8.3-xml php8.3-mbstring php8.3-gd` if using Ubuntu
2. Install Node via [nvm](https://github.com/nvm-sh/nvm)
3. Install dependencies and run app build with `make`
4. Mount this repo in the Nextcloud docker image
   with `docker run --rm -p 8080:80 -v ~/path/to/diary:/var/www/html/apps/diary ghcr.io/juliushaertl/nextcloud-dev-php83:latest`.
   Make sure to update the first path to the root of this repo.

* You can set a specific version with `-e SERVER_BRANCH=version`, where `version` is a branch or tag. For example, to
  run it on Nextcloud 31,
  run `docker run --rm -p 8080:80 -e SERVER_BRANCH=stable31 -v ~/path/to/diary:/var/www/html/apps/diary ghcr.io/juliushaertl/nextcloud-dev-php83:latest`

5. In another terminal process, enable continuous builds by running `npm run watch`
6. Navigate to the app in your browser at `localhost:8080`
7. Login with `admin` / `admin`
8. Go to "Apps" in main menu
9. Scroll to "Diary" authorize enabling the untested app. The page will reload. Scroll down to "Diary" again and enable
   it.

The Diary app will now be available in Nextcloud and will reflect the state of the local repo.
