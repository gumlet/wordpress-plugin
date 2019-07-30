Guide to release new version of this plugin.

0. First of all change stable tag version in `gumlet.php` and `readme.txt` files.
1. Copy everything from this directory to SVN directory's trunk folder.
2. Issue command `svn diff` in svn directory to check changes.
3. Commit things `svn ci -m "commit message"`
4. Copy everything from trunk to tags. `svn cp trunk tags/1.0.x`
5. Commit new tag `svn ci -m "tagging version 1.0.x"`
