# cebe/markdown (vendored)

The Markdown parser behind iPin Modern's Markdown posts, articles and comments.

- Source: https://github.com/cebe/markdown, `master` at commit
  `2b2461bed9e15305486319ee552bafca75d1cdaa` (26 February 2020, 1.2.2-dev).
- Licence: MIT, see `LICENSE`. MIT code may be combined with the theme's
  GPL-2.0-or-later code.
- Files kept: `Parser.php`, `Markdown.php`, `GithubMarkdown.php`, `block/`
  and `inline/`, unchanged. Tests, the CLI, `MarkdownExtra.php` and the CI
  files are left out.
- Loaded on demand by the autoloader in `inc/class-ipin-markdown.php`, and
  only when a page needs Markdown. Namespace `cebe\markdown`: if a plugin
  already loaded the same library, that copy is used.
- Checked against the library's own test fixtures on PHP 8.3: all 62 pass
  (Markdown 47, GitHub flavour 15), with no notices or deprecations.

To update: replace these files with the same set from a newer release and
run the fixtures again.
