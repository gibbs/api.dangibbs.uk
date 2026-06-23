<?php

it('redirects the root URL to the current docs', function () {
    $this->get('/')->assertRedirect('/docs#/');
});
