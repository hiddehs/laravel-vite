<?php

namespace Innocenzi\Vite;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Stringable;

class ManifestEntry implements Htmlable, Stringable {
	public string $file;
	public string $src;
	public bool $isEntry;
	public bool $isDynamicEntry;
	public Collection $css;
	public Collection $dynamicImports;

	/**
	 * Generates a manifest entry from an array.
	 */
	public static function fromArray( array $manifestEntry ): ManifestEntry {
		$entry                 = new ManifestEntry();
		$entry->src            = $manifestEntry['src'] ?? '';
		$entry->file           = $manifestEntry['file'] ?? '';
		$entry->isEntry        = $manifestEntry['isEntry'] ?? false;
		$entry->isDynamicEntry = $manifestEntry['isDynamicEntry'] ?? false;
		$entry->dynamicImports = Collection::make( $manifestEntry['dynamicImports'] ?? [] );
		$entry->css            = Collection::make( $manifestEntry['css'] ?? [] );

		return $entry;
	}

	/**
	 * Gets the script tag for this entry.
	 */
	public function getScriptTag(): string {
		return sprintf( '<script type="module" src="%s"%s></script>', $this->asset( $this->file ), $this->getNonceTag() );
	}

	/**
	 * Gets the style tags for this entry.
	 */
	public function getStyleTags(): Collection {
		return $this->css->map( fn( string $path ) => sprintf( '<link rel="stylesheet" href="%s"%s/>', $this->asset( $path ), $this->getNonceTag() ) );
	}

	/**
	 * Gets every appliacable tag.
	 */
	public function getTags(): Collection {
		return Collection::make()
		                 ->push( $this->getScriptTag() )
		                 ->merge( $this->getStyleTags() );
	}

	/**
	 * Gets the complete path for the given asset path.
	 */
	protected function asset( string $path ): string {
		return asset( sprintf( '/%s/%s', config( 'vite.build_path' ), $path ) );
	}

	/**
	 * Gets nonce function name from config and calls it to get the asset nonce
	 */
	public function getNonceTag(): string {
		if ( config( 'vite.csp_nonce.enabled' ) ) {
			return " nonce='" . $this->getNonce() . "'";
		}

		return '';
	}

	private function getNonce() {
		return call_user_func( config( 'vite.csp_nonce.function' ) );
	}

	/**
	 * Gets the resources for this entry.
	 *
	 * @return string
	 */
	public function toHtml() {
		return $this->getTags()->join( '' );
	}

	public function __toString() {
		return $this->toHtml();
	}
}
