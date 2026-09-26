<?php
/**
 * iPin Modern — Ipin_Markdown
 *
 * The Markdown parser for posts, articles and comments: cebe/markdown's
 * GitHub-flavoured parser (inc/vendor/cebe-markdown/, MIT) plus what the
 * theme adds on top:
 *
 *   Headings     get an id from their text, so #anchor links work.
 *   Task lists   "- [ ] todo" and "- [x] done" become li.task-item and
 *                li.task-item--done (drawn by assets/css/markdown.css).
 *   Tables       are wrapped in div.ipin-table-wrap so wide ones scroll.
 *   Code blocks  keep their language class, reduced to a safe class name,
 *                and repeat it as <pre data-lang> for the language badge.
 *   Links and images with a scheme WordPress does not allow (javascript:,
 *                data: and the like) are dropped: a link keeps its text,
 *                an image disappears.
 *
 * The output still goes through wp_kses_post() (posts) or the comment tag
 * allow-list (comments) in inc/markdown.php.
 *
 * Loaded by ipin_markdown_parser() in inc/markdown.php, after it has
 * registered the autoloader for cebe\markdown, and only on first use.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

class Ipin_Markdown extends \cebe\markdown\GithubMarkdown {

	/** Headings: add an id made from the heading text. */
	protected function renderHeadline( $block ) {
		$html = parent::renderHeadline( $block );
		$id   = sanitize_title( wp_strip_all_tags( $html ) );
		if ( '' === $id ) {
			return $html;
		}
		return (string) preg_replace( '/^<h([1-6])>/', '<h$1 id="' . esc_attr( $id ) . '">', $html, 1 );
	}

	/** Lists: turn "[ ] " and "[x] " at the start of an item into task items. */
	protected function renderList( $block ) {
		$html = parent::renderList( $block );
		return (string) preg_replace_callback(
			'#<li>(<p>)?\[( |x|X)\]\s+#',
			static fn( array $m ): string => '<li class="task-item' . ( ' ' === $m[2] ? '' : ' task-item--done' ) . '">' . $m[1],
			$html
		);
	}

	/** Tables: wrap so wide tables scroll inside the post. */
	protected function composeTable( $head, $body ) {
		return '<div class="ipin-table-wrap">' . parent::composeTable( $head, $body ) . "</div>\n";
	}

	/**
	 * Code blocks: escape the language hint before it becomes a class name,
	 * and repeat it on <pre data-lang> for the badge in markdown.css.
	 */
	protected function renderCode( $block ) {
		$lang = '';
		if ( isset( $block['language'] ) ) {
			$lang = sanitize_html_class( (string) $block['language'] );
			if ( '' === $lang ) {
				unset( $block['language'] );
			} else {
				$block['language'] = $lang;
			}
		}
		$html = parent::renderCode( $block );
		if ( '' !== $lang ) {
			$html = (string) preg_replace( '/^<pre>/', '<pre data-lang="' . esc_attr( $lang ) . '">', $html, 1 );
		}
		return $html;
	}

	/** Links: drop the link, keep its text, when the scheme is not allowed. */
	protected function renderLink( $block ) {
		$html = parent::renderLink( $block );
		if ( preg_match( '/^<a href="([^"]*)"/', $html, $m ) && ! self::is_allowed_url( $m[1] ) ) {
			return $this->renderAbsy( $block['text'] );
		}
		return $html;
	}

	/** Images: drop the image when the scheme is not allowed. */
	protected function renderImage( $block ) {
		$html = parent::renderImage( $block );
		if ( preg_match( '/^<img src="([^"]*)"/', $html, $m ) && ! self::is_allowed_url( $m[1] ) ) {
			return '';
		}
		return $html;
	}

	/** True when WordPress would keep this (HTML-escaped) URL. */
	private static function is_allowed_url( string $escaped ): bool {
		$url = html_entity_decode( $escaped, ENT_QUOTES | ENT_HTML401, 'UTF-8' );
		return '' !== esc_url( $url );
	}
}
