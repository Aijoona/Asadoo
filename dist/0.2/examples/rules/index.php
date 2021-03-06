<?php

include '../../dist/asadoo.php';

// Multiples rules
asadoo()
    // Capture by string rule with arguments
    ->on('/view/:id/:referral')
    ->on('/view/:id')
    // Capture by string without arguments
    ->on('/view')
    // Functional capture, matchs /foo/bar/view/baz
    ->on(function($request, $response, $dependences) {
        return $request->has('view');
    })
    ->handle(function($request, $response, $dependences) {
        // Captured arguments
        $id = $request->value('id', 'Not found!');
        $referral =  $request->value('referral', 'Not found!');

        // Response body
        $response->write('ID: ' . $id);
        $response->write('<br />');
        $response->write('Referral: ' . $referral);

        $response->end();
    });

// Index
asadoo()
    // All requests
    ->on('*')
    ->handle(function($request, $response, $dependences) {
        $response->write(
            '<html><head><title>Rules</title></head><body><ul>',
            '<li><a href="view/1/batman">view/1/batman</a></li>',
            '<li><a href="view/1">view/1</a></li>',
            '<li><a href="view">view</a></li>',
            '<li><a href="batman/superman/viewman">batman/superman/viewman</a></li>'
        );
    });

// Aaaaand, go!
asadoo()->start();