<?php

use HiFolks\DataType\Block;

test(
    'Block set() from empty Block',
    function () {
        $articleText = "Some words as a sample sentence";
        $textField = Block::make();
        $textField->set("type", "doc");
        $textField->set("content.0.content.0.text", $articleText);
        $textField->set("content.0.content.0.type", "text");
        $textField->set("content.0.type", "paragraph");

        expect($textField->get('content.0.content.0.text'))->toBe($articleText);
        expect($textField->get('content.0.content.0'))->toBeArray();
        expect($textField->get('content.0.content.0'))->toHaveCount(2);
        expect($textField->get('content.0.content.0.newfield'))->toBeNull();
        $textField->set("content.0.content.0.newfield", "THIS IS A NEW FIELD");
        expect($textField->get('content.0.content.0.newfield'))->toBeString();
        expect($textField->get('content.0.content.0.newfield'))->toBe("THIS IS A NEW FIELD");
        expect($textField->get('content.0.content.0'))->toBeArray();
        expect($textField->get('content.0.content.0'))->toHaveCount(3);

    },
);
