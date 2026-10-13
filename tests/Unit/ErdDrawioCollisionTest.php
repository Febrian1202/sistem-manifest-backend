<?php

test('drawio diagram has zero vertex bounding box collisions', function () {
    $filePath = dirname(__DIR__, 2).'/docs/diagrams/erd/erd-chen-audit-manifest.drawio';
    expect(file_exists($filePath))->toBeTrue();

    $xml = simplexml_load_file($filePath);
    expect($xml)->not->toBeFalse();

    $boxes = [];
    foreach ($xml->diagram->mxGraphModel->root->mxCell as $cell) {
        if ((string) $cell['vertex'] === '1' && isset($cell->mxGeometry)) {
            $id = (string) $cell['id'];
            if ($id === 'title') {
                continue; // title text is at the top banner
            }
            $geo = $cell->mxGeometry;
            $boxes[$id] = [
                'id' => $id,
                'value' => (string) $cell['value'],
                'x' => (float) $geo['x'],
                'y' => (float) $geo['y'],
                'w' => (float) $geo['width'],
                'h' => (float) $geo['height'],
            ];
        }
    }

    $collisions = [];
    $ids = array_keys($boxes);
    $count = count($ids);

    for ($i = 0; $i < $count; $i++) {
        for ($j = $i + 1; $j < $count; $j++) {
            $b1 = $boxes[$ids[$i]];
            $b2 = $boxes[$ids[$j]];

            // Check if bounding boxes overlap with at least 1px tolerance
            $overlapX = max(0, min($b1['x'] + $b1['w'], $b2['x'] + $b2['w']) - max($b1['x'], $b2['x']));
            $overlapY = max(0, min($b1['y'] + $b1['h'], $b2['y'] + $b2['h']) - max($b1['y'], $b2['y']));

            if ($overlapX > 0 && $overlapY > 0) {
                $collisions[] = "{$b1['id']} ({$b1['value']}) overlaps with {$b2['id']} ({$b2['value']}) [overlap: {$overlapX}x{$overlapY}px]";
            }
        }
    }

    expect($collisions)->toBeEmpty('Detected bounding box overlaps: '.implode('; ', $collisions));
});
