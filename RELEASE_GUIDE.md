Guide to release new version of this plugin.

1. First of all change stable tag version in `gumlet.php` and `readme.txt` files.
2. Tag the SAME version in git repo and push it.
3. https://github.com/10up/action-wordpress-plugin-deploy this github action automatically deploys the plugin.

The Github action can be found in `.github/workflows/main.yml` file.
