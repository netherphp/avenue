<?php

namespace TestRoutes;

use Nether;

class Sorts
extends Nether\Avenue\Route {

	// expected ordering reasons
	// SortA - static domain, segment count sort, wildcard sort
	// SortZ - static domain, domain sort
	// SortX - static domain, domain sort
	// SortY - static domain, domain sort
	// SortB - wild domain, method sort
	// SortC - wild domain, method sort

	#[Nether\Avenue\Meta\RouteHandler('/sort')]
	public function
	SortB():
	void {

		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/sort/:Key:')]
	public function
	SortA():
	void {

		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/sort')]
	public function
	SortC():
	void {

		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/sort', 'x-domain.tld')]
	public function
	SortZ():
	void {

		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/sort', 'y-domain.tld')]
	public function
	SortX():
	void {

		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/sort', 'z-domain.tld')]
	public function
	SortY():
	void {

		return;
	}

}
