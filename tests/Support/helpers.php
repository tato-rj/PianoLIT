<?php

function create($class, $attributes = [], $amount = null)
{
	return factory($class, $amount)->create($attributes);
}

function make($class, $attributes = [], $amount = null)
{
	return factory($class, $amount)->make($attributes);
}
