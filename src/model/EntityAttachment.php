<?php

namespace spf\model;

/**
 * EntityAttachments provide a mechanism by which related items can be incorporated into an entity.
 * They relieve us of the need to have loadFoo() methods scattered across repositories, the code to actually fetch the
 * related items and attach them to the entities is delgated to an EntityAttachement subclass - SRP and all that...
 *
 */
abstract class EntityAttachment {

	protected $entity_class;

	public function __construct( $entity ) {
		$this->entity_class = $entity;
	}

	/**
	 * This method is called by repositories to attach a set of related items to the specified entities.
	 *
	 * @param $items array|Entity   the entity or array of entities to attach related items to
	 * @return void
	 */
	abstract public function attach( $items );

	/**
	 * Implementations should call this function to ensure we're only dealing with items of the class we care about.
	 *
	 * @param $items array|Entity   the entity or array of entities to attach related items to
	 * @return array
	 */
	protected function filterItems( $items ) {

		$entity_class = $this->entity_class;

		if( $items instanceof $this->entity_class ) {
			$items = array($items->id => $items);
		}
		elseif( !is_array($items) ) {
			throw new \InvalidArgumentException(sprintf("\\%s::%s() expects %s or array(), '%s' given", get_class($this), __FUNCTION__, $this->entity_class, \spf\var_info($items)));
		}

		// make sure we're only dealing with instances of ContentItem
		return array_filter(
			$items,
			function( $item ) use ($entity_class) {
				return ($item instanceof $entity_class);
			}
		);

	}

}

// EOF