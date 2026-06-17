<?php

namespace BlueSpice\Bookshelf\Renderer;

use MWStake\MediaWiki\Component\CommonUserInterface\ComponentManager;
use MWStake\MediaWiki\Component\CommonUserInterface\RendererDataTreeBuilder;
use MWStake\MediaWiki\Component\CommonUserInterface\RendererDataTreeRenderer;

class ComponentRenderer {

	/** @var callable */
	private $componentManagerCallback;

	/** @var ComponentManager|null */
	private ?ComponentManager $componentManager = null;

	/** @var RendererDataTreeBuilder */
	private $rendererDataTreeBuilder = null;

	/** @var RendererDataTreeRenderer */
	private $rendererDataTreeRenderer = null;

	/**
	 * @param callable $componentManagerCallback
	 * @param RendererDataTreeBuilder $rendererDataTreeBuilder
	 * @param RendererDataTreeRenderer $rendererDataTreeRenderer
	 */
	public function __construct(
		callable $componentManagerCallback,
		RendererDataTreeBuilder $rendererDataTreeBuilder,
		RendererDataTreeRenderer $rendererDataTreeRenderer ) {
		$this->componentManagerCallback = $componentManagerCallback;
		$this->rendererDataTreeBuilder = $rendererDataTreeBuilder;
		$this->rendererDataTreeRenderer = $rendererDataTreeRenderer;
	}

	private function getComponentManager(): ComponentManager {
		if ( $this->componentManager === null ) {
			$this->componentManager = ( $this->componentManagerCallback )();
		}
		return $this->componentManager;
	}

	/**
	 * @param IComponent $component
	 * @param array $componentProcessData
	 * @return string
	 */
	public function getComponentHtml( $component, $componentProcessData = [] ): string {
		$componentTree = $this->getComponentManager()->getCustomComponentTree(
			$component,
			$componentProcessData
		);
		if ( empty( $componentTree ) ) {
			return '';
		}

		$rendererDataTree = $this->rendererDataTreeBuilder->getRendererDataTree( [ array_pop( $componentTree ) ] );

		$html = $this->rendererDataTreeRenderer->getHtml( $rendererDataTree );

		return $html;
	}
}
