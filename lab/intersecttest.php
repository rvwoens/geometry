<?php

//https://github.com/vrd/js-intersect


function intersect($fig1, $fig2) {
    for($i=0; $i<count($fig1); $i++) {
        $fig1[$i]['x']=+(floatval($fig1[$i]['x']));    // . toFixed(9));
        $fig1[$i]['y']=+(floatval($fig1[$i]['y']));    // . toFixed(9));
    }
    for($i=0; $i<count($fig2); $i++) {
        $fig2[$i]['x']=+(floatval($fig2[$i]['x'])); //. toFixed(9));
        $fig2[$i]['y']=+(floatval($fig2[$i]['y'])); //. toFixed(9));
    }
    $fig2a=alignPolygon($fig2, $fig1);

    if(!checkPolygons($fig1, $fig2a)) {
        return false;
    }
    $edges=edgify($fig1, $fig2a);
    tlog($edges);
    $polygons=polygonate($edges);
    $filteredPolygons=filterPolygons($polygons, $fig1, $fig2a, "intersect");
    return $filteredPolygons;
}

function alignPolygon($polygon, $points) {
    for($i=0; $i<count($polygon); $i++) {
        for($j=0; $j<count($points); $j++) {
            if(distance($polygon[$i], $points[$j])<0.00000001) {
                $polygon[$i]=$points[$j];
            }
        }
    }
    return $polygon;
}

function distance($p1, $p2) {
    $dx=abs(floatval($p1['x']) - floatval($p2['x']));
    $dy=abs(floatval($p1['y']) - floatval($p2['y']));
    return sqrt($dx * $dx + $dy * $dy);
}

//check polygons for correctness
function checkPolygons($fig1, $fig2) {
    $figs=[$fig1, $fig2];
    for($i=0; $i<count($figs); $i++) {
        if(count($figs[$i])<3) {
            error_log("Polygon " . ($i + 1) . " is invalid!");
            return false;
        }
    }
    return true;
}

//create array of edges of all polygons
function edgify($fig1, $fig2) {
    //create primary array from all edges
    $primEdges=array_merge(getEdges($fig1), getEdges($fig2));
    $secEdges=[];
    //check every edge
    for($i=0; $i<count($primEdges); $i++) {
        $points=[];
        //for intersection with every edge except itself
        for($j=0; $j<count($primEdges); $j++) {
            if($i != $j) {
                $interPoints=findEdgeIntersection($primEdges[$i], $primEdges[$j]);
                addNewPoints($interPoints, $points);
            }
        }
        //add start and end points to intersection points
        $startPoint=$primEdges[$i][0];
        $startPoint['t']=0;
        $endPoint=$primEdges[$i][1];
        $endPoint['t']=1;
        addNewPoints([$startPoint, $endPoint], $points);
        //sort all points by position on edge
        $points=sortPoints($points);
        //break edge to parts
        for($k=0; $k<count($points) - 1; $k++) {
            $edge=[
                ['x'=>$points[$k]['x'], 'y'=>$points[$k]['y']],
                ['x'=>$points[$k + 1]['x'], 'y'=>$points[$k + 1]['y']]
            ];
            // check for existanse in sec.array
            if(!edgeExists($edge, $secEdges)) {
                //push if not exists
                $secEdges[]=$edge;
            }
        }
    }
    return $secEdges;
}

function addNewPoints($newPoints, &$points) {
    if(count($newPoints)>0) {
        //check for uniqueness
        for($k=0; $k<count($newPoints); $k++) {
            if(!pointExists($newPoints[$k], $points)) {
                $points[]=$newPoints[$k];
            }
        }
    }
}

function sortPoints($points) {
    $p=$points;
    usort($p, function($a, $b) {
        if($a['t']>$b['t']) return 1;
        if($a['t']<$b['t']) return -1;
    });
    return $p;
}

function getEdges($fig) {
    $edges=[];
    if ($fig===false)
        return [];
    $len=count($fig);
    for($i=0; $i<$len; $i++) {
        $edges[]=[
            ['x'=>$fig[($i % $len)]['x'], 'y'=>$fig[($i % $len)]['y']],
            ['x'=>$fig[(($i + 1) % $len)]['x'], 'y'=>$fig[(($i + 1) % $len)]['y']]
        ];
    }
    return $edges;
}

function findEdgeIntersection($edge1, $edge2) {
    $x1=$edge1[0]['x'];
    $x2=$edge1[1]['x'];
    $x3=$edge2[0]['x'];
    $x4=$edge2[1]['x'];
    $y1=$edge1[0]['y'];
    $y2=$edge1[1]['y'];
    $y3=$edge2[0]['y'];
    $y4=$edge2[1]['y'];
    $nom1=($x4 - $x3) * ($y1 - $y3) - ($y4 - $y3) * ($x1 - $x3);
    $nom2=($x2 - $x1) * ($y1 - $y3) - ($y2 - $y1) * ($x1 - $x3);
    $denom=($y4 - $y3) * ($x2 - $x1) - ($x4 - $x3) * ($y2 - $y1);
    $interPoints=[];
    if ($denom==0)
        return $interPoints;
    $t1=$nom1 / $denom;
    $t2=$nom2 / $denom;

    //1. lines are parallel or edges don't intersect
    if((($denom === 0) && ($nom1 !== 0)) || ($t1<=0) || ($t1>=1) || ($t2<0) || ($t2>1)) {
        return $interPoints;
    } //2. lines are collinear
    else if(($nom1 === 0) && ($denom === 0)) {
        //check if endpoints of edge2 lies on edge1
        for($i=0; $i<2; $i++) {
            $classify=classifyPoint($edge2[$i], $edge1);
            //find position of this endpoints relatively to edge1
            if($classify['loc'] == "ORIGIN" || $classify['loc'] == "DESTINATION") {
                array_push($interPoints, ['x'=>$edge2[$i]['x'], 'y'=>$edge2[$i]['y'], 't'=>$classify['t']]);
            } else if($classify['loc'] == "BETWEEN") {
                $x=round(($x1 + $classify['t'] * ($x2 - $x1)), 9);
                $y=round(($y1 + $classify['t'] * ($y2 - $y1)), 9);
                array_push($interPoints, ['x'=>$x, 'y'=>$y, 't'=>$classify['t']]);
            }
        }
        return $interPoints;
    } //3. edges intersect
    else {
        for($i=0; $i<2; $i++) {
            $classify=classifyPoint($edge2[$i], $edge1);
            if($classify['loc'] == "ORIGIN" || $classify['loc'] == "DESTINATION") {
                array_push($interPoints, ['x'=>$edge2[$i]['x'], 'y'=>$edge2[$i]['y'], 't'=>$classify['t']]);
            }
        }
        if(count($interPoints)>0) {
            return $interPoints;
        }
        $x=round(($x1 + $t1 * ($x2 - $x1)), 9);
        $y=round(($y1 + $t1 * ($y2 - $y1)), 9);
        array_push($interPoints, ['x'=>$x, 'y'=>$y, 't'=>$t1]);
        return $interPoints;
    }
}

function classifyPoint($p, $edge) {
    $ax=$edge[1]['x'] - $edge[0]['x'];
    $ay=$edge[1]['y'] - $edge[0]['y'];
    $bx=$p['x'] - $edge[0]['x'];
    $by=$p['y'] - $edge[0]['y'];
    $sa=$ax * $by - $bx * $ay;
    if(($p['x'] === $edge[0]['x']) && ($p['y'] === $edge[0]['y'])) {
        return ['loc'=>"ORIGIN", 't'=>0];
    }
    if(($p['x'] === $edge[1]['x']) && ($p['y'] === $edge[1]['y'])) {
        return ['loc'=>"DESTINATION", 't'=>1];
    }
    $theta=(polarAngle([$edge[1], $edge[0]]) - polarAngle( ['x'=>$edge[1]['x'], 'y'=>$edge[1]['y'] ],
                                                          [ 'x'=>$p['x'], 'y'=>$p['y'] ])) % 360;
    if($theta<0) {
        $theta=$theta + 360;
    }
    if($sa<-0.000000001) {
        return ['loc'=>"LEFT", 'theta'=>$theta];
    }
    if($sa>0.000000001) {
        return ['loc'=>"RIGHT", 'theta'=>$theta];
    }
    if((($ax * $bx)<0) || (($ay * $by)<0)) {
        return ['loc'=>"BEHIND", 'theta'=>$theta];
    }
    if((sqrt($ax * $ax + $ay * $ay))<(sqrt($bx * $bx + $by * $by))) {
        return ['loc'=>"BEYOND", 'theta'=>$theta];
    }
    if($ax != 0) {
        $t=$bx / $ax;
    } else {
        $t=$by / $ay;
    }
    return ['loc'=>"BETWEEN", 't'=>$t];
}
function polarAngle($edge) {
    // rvwrvw
    if (!isset($edge[1]) || !isset($edge[0]))
        return false;
    $dx = $edge[1]['x'] - $edge[0]['x'];
    $dy = $edge[1]['y'] - $edge[0]['y'];
    if (($dx === 0) && ($dy === 0)) {
        //echo "Edge has zero length.";
        return false;
    }
    if ($dx == 0) {
        return (($dy > 0) ? 90 : 270);
    }
    if ($dy == 0) {
        return (($dx > 0) ? 0 : 180);
    }
    $theta = atan($dy/$dx)*360/(2*pi());
    if ($dx > 0) {
        return (($dy >= 0) ? $theta : $theta + 360);
    } else {
        return ($theta + 180);
    }
}
function pointExists($p, $points) {
    if (count($points) === 0) {
        return false;
    }
    for ($i = 0; $i < count($points); $i++) {
        if (($p['x'] === $points[$i]['x']) && ($p['y'] === $points[$i]['y'])) {
            return true;
        }
    }
    return false;
}
function edgeExists($e, $edges) {
    if (count($edges) === 0) {
        return false;
    }
    for ($i = 0; $i < count($edges); $i++) {
        if (equalEdges($e, $edges[$i]))
            return true;
    }
    return false;
}
function equalEdges($edge1, $edge2) {
    if ((($edge1[0]['x'] == $edge2[0]['x']) &&
            ($edge1[0]['y'] == $edge2[0]['y']) &&
            ($edge1[1]['x'] == $edge2[1]['x']) &&
            ($edge1[1]['y'] == $edge2[1]['y'])) || (
            ($edge1[0]['x'] == $edge2[1]['x']) &&
            ($edge1[0]['y'] == $edge2[1]['y']) &&
            ($edge1[1]['x'] == $edge2[0]['x']) &&
            ($edge1[1]['y'] == $edge2[0]['y']))) {
        return true;
    } else {
        return false;
    }
}
function polygonate($edges) {
    $polygons = [];
    $polygon = [];
    $len = count($edges);
    $midpoints = getMidpoints($edges);
    //start from every edge and create non-selfintersecting polygons
    for ($i = 0; $i < $len - 2; $i++) {
        $org = ['x' => $edges[$i][0]['x'], 'y' => $edges[$i][0]['y']];
        $dest = ['x' => $edges[$i][1]['x'], 'y' => $edges[$i][1]['y']];
        $currentEdge = $i;
        //while we havn't come to the starting edge again
        for ($direction = 0; $direction < 2; $direction++) {
            $polygon = [];
            $stop = false;
            while (count($polygon) == 0 || (!$stop)) {
                echo ".";
                //add point to polygon
                $polygon[] = ['x' => $org['x'], 'y' => $org['y']];
                $point = null;
                //look for edge connected with end of current edge
                for ($j = 0; $j < $len; $j++) {
                    $p = null;
                    //except itself
                    if (!equalEdges($edges[$j], $edges[$currentEdge])) {
                        //if some edge is connected to current edge in one endpoint
                        if (($edges[$j][0]['x'] == $dest['x']) && ($edges[$j][0]['y'] == $dest['y'])) {
                            $p = $edges[$j][1];
                        }
                        if (($edges[$j][1]['x'] == $dest['x']) && ($edges[$j][1]['y'] == $dest['y'])) {
                            $p = $edges[$j][0];
                        }
                        //compare it with last found connected edge for minimum angle between itself and current edge
                        if ($p) {
                            $classify = classifyPoint($p, [$org, $dest]);
                            //if this edge has smaller theta then last found edge update data of next edge of polygon
                            if (!$point ||
                                (($classify['theta'] > $point['theta']) && ($direction === 1))) {
                                $point = ['x' => $p['x'], 'y' => $p['y'], 'theta' => $classify['theta'], 'edge' => $j];
                            }
                        }
                    }
                }
                //change current edge to next edge
                $org['x'] = $dest['x'];
                $org['y'] = $dest['y'];
                $dest['x'] = $point['x'];
                $dest['y'] = $point['y'];
                $currentEdge = $point['edge'];
                //if we reach start edge
                if (equalEdges([$org, $dest], $edges[$i])) {
                    $stop = true;
                    //check polygon for correctness
                    /*for ($k = 0; $k < $allPoints.length; $k++) {
                      //if some point is inside polygon it is incorrect
                      if ((!pointExists($allPoints[$k], $polygon)) && (findPointInsidePolygon($allPoints[$k], $polygon))) {
                        $polygon = false;
                      }
                    }*/
                    for ($k = 0; $k < count($midpoints); $k++) {
                        //if some midpoint is inside polygon (edge inside polygon) it is incorrect
                        if (findPointInsidePolygon($midpoints[$k], $polygon)) {
                            $polygon=false;
                        }
                    }
                    break;  //
                }
            }
            //add created polygon if it is correct and was not found before
            if ($polygon && !polygonExists($polygon, $polygons)) {
                $polygons[] = $polygon;
            }
        }
    }
    //console.log("polygonate: " + JSON.stringify(polygons));
    return $polygons;
}

function polygonExists($polygon, $polygons) {
    //if array is empty element doesn't exist in it
    if (count($polygons) === 0) return false;
    //check every polygon in array
    for ($i = 0; $i < count($polygons); $i++) {
        //if lengths are not same go to next element
        if (count($polygon) !== count($polygons[$i])) continue;
        //if length are same need to check
        else {
            //if all the points are same
            for ($j = 0; $j < count($polygon); $j++) {
                //if point is not found break forloop and go to next element
                if (!pointExists($polygon[$j], $polygons[$i])) break;
                //if point found
                else {
                    //and it is last point in polygon we found polygon in array!
                    if ($j === count($polygon) - 1) return true;
                }
            }
        }
    }
    return false;
}

function filterPolygons($polygons, $fig1, $fig2, $mode) {
    $filtered = [];
    $c1;
    $c2;
    $point;
    $bigPolygons = removeSmallPolygons($polygons, 0.0001);
    for($i = 0; $i < count($bigPolygons); $i++) {
        $point = getPointInsidePolygon($bigPolygons[$i]);
        $c1 = findPointInsidePolygon($point, $fig1);
        $c2 = findPointInsidePolygon($point, $fig2);
        if (
            (($mode === "intersect") && $c1 && $c2) || //intersection
            (($mode === "cut1") && $c1 && !$c2) ||     //fig1 - fig2
            (($mode === "cut2") && !$c1 && $c2) ||     //fig2 - fig2
            (($mode === "sum") && ($c1 || $c2))) {     //fig1 + fig2
            array_push($filtered, $bigPolygons[$i]);
        }
    }
    //echo "filtered: " . json_encode($filtered);
    return $filtered;
}

function removeSmallPolygons($polygons, $minSize) {
    $big = [];
    for ($i = 0; $i < count($polygons); $i++) {
        if (polygonArea($polygons[$i]) >= $minSize) {
            array_push($big, $polygons[$i]);
        }
    }
    return $big;
}

function polygonArea($p) {
    $len = count($p);
    $s = 0;
    for ($i = 0; $i < $len; $i++) {
        $s += abs(($p[$i % $len]['x'] * $p[($i + 1) % $len]['y']) - ($p[$i % $len]['y'] * $p[($i + 1) % $len]['x']));
    }
    return $s/2;
}
function getPointInsidePolygon($polygon) {
    $point;
    $size = getSize($polygon);
    $edges = getEdges($polygon);
    $y = $size['y']['min'] + ($size['y']['max'] - $size['y']['min']) / M_PI;
    $dy = ($size['y']['max'] - $size['y']['min']) / 13;
    $line = [];
    $points;
    $interPoints = [];
    $pointsOK = false;
    while (!$pointsOK) {
        $line = [['x' => ($size['x']['min'] - 1), 'y' => $y],['x' => ($size['x']['max'] + 1), 'y' => $y]];
        //find intersections with all polygon edges
        for ($i = 0; $i < count($edges); $i++) {
            $points = findEdgeIntersection($line, $edges[$i]);
            //if edge doesn't lie inside line
            if ($points && (count($points) === 1)) {
                array_push($interPoints, $points[0]);
            }
        }
        $interPoints = sortPoints($interPoints);
        //find two correct interpoints
        for ($i = 0; $i < count($interPoints) - 1; $i++) {
            if ($interPoints[$i]['t'] !== $interPoints[$i+1]['t']) {
                //enable exit from loop and calculate point coordinates
                $pointsOK = true;
                $point = ['x' => (($interPoints[$i]['x'] + $interPoints[$i+1]['x']) / 2), 'y' => $y];
            }
        }
        //all points are incorrect, need to change line parameters
        $y = $y + $dy;
        if ((($y > $size['y']['max']) || ($y < $size['y']['min'])) && ($pointsOK === false)) {
            $pointsOK = true;
            $point = null;
        }
    }
    return $point;
}

function getSize($polygon) {
    $size = [        'x' => [            'min' => $polygon[0]['x'],
        'max' => $polygon[0]['x']
    ],
        'y' => [            'min' => $polygon[0]['y'],
            'max' => $polygon[0]['y']
        ]
    ];
    for ($i = 1; $i < count($polygon); $i++) {
        if ($polygon[$i]['x'] < $size['x']['min']) $size['x']['min'] = $polygon[$i]['x'];
        if ($polygon[$i]['x'] > $size['x']['max']) $size['x']['max'] = $polygon[$i]['x'];
        if ($polygon[$i]['y'] < $size['y']['min']) $size['y']['min'] = $polygon[$i]['y'];
        if ($polygon[$i]['y'] > $size['y']['max']) $size['y']['max'] = $polygon[$i]['y'];
    }
    return $size;
}

function findPointInsidePolygon($point, $polygon) {
    $cross = 0;
    $edges = getEdges($polygon);
    $classify;
    $org;
    $dest;
    for ($i = 0; $i < count($edges); $i++) {
        [$org, $dest] = $edges[$i];
        $classify = classifyPoint($point, [$org, $dest]);
        if (  (
                ($classify['loc'] === "RIGHT") &&
                ($org['y'] < $point['y']) &&
                ($dest['y'] >= $point['y'])
            ) ||
            (
                ($classify['loc'] === "LEFT") &&
                ($org['y'] >= $point['y']) &&
                ($dest['y'] < $point['y'])
            )
        ) {
            $cross++;
        }
        if ($classify['loc'] === "BETWEEN") return false;
    }
    if ($cross % 2) {
        return true;
    } else {
        return false;
    }
}

function getMidpoints($edges) {
    $midpoints = [];
    $x;
    $y;
    for ($i = 0; $i < count($edges); $i++) {
        $x = ($edges[$i][0]['x'] + $edges[$i][1]['x']) / 2;
        $y = ($edges[$i][0]['y'] + $edges[$i][1]['y']) / 2;
        $classify = classifyPoint(['x' => $x, 'y' => $y], $edges[$i]);
        if ($classify['loc'] !== "BETWEEN") {
            error_log("Midpoint calculation error");
        }
        array_push($midpoints, ['x' => $x, 'y' => $y]);
    }
    return $midpoints;
}


function tlog($obj) {
    echo "Tlog:".json_encode($obj)."\n";
}


//$fig1 = [   ['x' =>  5.35328472172063, 'y' =>  3.3464605876540254],
//    ['x' => 31.10025450900146, 'y' =>  3.3464605876540254],
//    ['x' => 31.10025450900146, 'y' => 38.65353941234598  ],
//    ['x' =>  5.35328472172063, 'y' => 38.65353941234598  ]
//];
//$fig2 = [   ['x' => 31.10025450900146, 'y' => 6.964961212615723 ],
//    ['x' =>  5.35328472172063, 'y' => 3.3464605876540254],
//    ['x' => 26.64671527827937, 'y' => 38.65353941234598 ]
//];

// default test
$fig1 = [   ['x' => 100, 'y' => 200],
    ['x' => 300, 'y' => 150],
    ['x' => 300, 'y' => 250]
];
$fig2 = [   ['x' => 200, 'y' => 100],
    ['x' => 200, 'y' => 300],
    ['x' => 350, 'y' => 300],
    ['x' => 350, 'y' => 100]
];


echo "TEST\n";
$result=intersect($fig1, $fig2);
tlog($result);
// console.log("DEBUG STOPPED");
