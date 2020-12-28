<?php
class get_wishlist extends collection_base {
	public function getwishlist($sortby) {
		return $this->pull_wishlist($sortby);
	}
}
?>