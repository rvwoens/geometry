# geometry
[![GitHub Release](https://img.shields.io/github/v/tag/rvwoens/geometry.svg?style=flat)](//packagist.org/packages/rvwoens/geometry)
[![GitHub Release](https://img.shields.io/packagist/v/rvwoens/geometry.svg?style=flat)](//packagist.org/packages/rvwoens/geometry)
[![Total Downloads](https://poser.pugx.org/rvwoens/geometry/downloads)](//packagist.org/packages/rvwoens/geometry)
[![License](https://poser.pugx.org/rvwoens/geometry/license)](//packagist.org/packages/rvwoens/geometry)
[![Actions Status](https://github.com/rvwoens/geometry/workflows/CI/badge.svg)](https://github.com/rvwoens/geometry/actions)

Geometry 2D shapes helper functions

### Coord
A coord is a simple latitude / longitude coordinate class with some calculation and manipulation methods
```
// instantiation
$coord = new Coord(52.40010210009, 4.5776320002);

// properties
$lat=$coord->latitude;                  // latitude property
$lng=$coord->longitude;                 // longitude property

// methods
$coord2 = $coord->clone();              // clone the coord
$coord3 = $coord->movedClone(50, 180);  // make a clone and move it 50 meter southwards (bearing 180)
echo $coord->toString();                // "52.400102,4.577632"  6 digits significant (approx 10 cm)
echo $coord->toWktString();             // "4.577632 52.400102"  WKT (Well Known Text format)
if ($coord->equals($coord2)) ..         // A coord is equal when within a millimeter of another coord 
$dist = $coord->distance($coord3);      // calculate distance between coords in meters
$bearing = $coord->bearing($coord3);    // Bearing: 0 = north 90=east 180=south 270=west FROM coord TOWARDS coord3
$coord->move($distance,$bearing);       // mutable move of coord (use movedclone for immutable)
```

### Polygon
A polygon is a number of locations on a map defining an area. 
```
// instantiation. The last segment is automatically closed using the first coordinate
$polygon = new Polygon( [ [52.1,4.2], [52.2,4.2], [52.2,4.3] ]);                // define with simple lat/lng array
$polygon = new Polygon( [   ['lat'=>52.1,'lng'=>4.2], 
                            ['lat'=>52.2,'lng'=>4.2], 
                            ['lat'=>52.2,'lng'=>4.3] ]);                        // or using this format
$c1=new Coord(52.1,4.2);
$c2=new Coord(52.2,4.2);
$c3=new Coord(52.2,4.3);
$polygon = new Polygon( [ $c1,$c2,$c3 ]);                                       // or using an array of Coords
$polygon = new Polygon("POLYGON ((4.2 52.1, 52.2 4.2, 4.3 52.2, 4.2 52.1))");   // or using a WKT definition
$polygon = new Polygon("52.1,4.2|52.2,4.2|52.2,4.3");                           // or using the internal serialisation 

// methods
$polygon->valid();              // a polygon is valid when it is at least a triangle with 3 valid Coords
$polygon->size();               // number of sides of the polygon or n of the n-gon.
echo $polygon->polyString();    // to internal serialisation string "52.1,4.2|52.2,4.2|52.2,4.3"
echo $polygon->polyWktString(); // to WKT definition "POLYGON ((4.2 52.1, 52.2 4.2, 4.3 52.2, 4.2 52.1))"
json_encode($polygon->polyGeoJsonArray());  // convert to Geojson string
$polygon->polyGeoJsonString();              // convert to Geojson string
$polygon->polyLatLngArray();                // convert to array format [ ['lat'=>lat,'lng'=>lng]...]
$polygon->equals($polyon2);     // true when each coordinate is within 1mm 
$polygon->farAway($coord);      // true when polygon is "far away" from the coord to speed up some calculations
$polygon->contains($coord);     // true when Coord is inside the polygon
$polygon->areaSquareMeters();   // returns area of polygon in square meters using earth projection
$coord=$polygon->center();      // calculate the center of mass of the polygon as a Coord

// note: smallest outer circle and largest inner circle are simplifications. Both use polygon center
$radius=$polygon->smallestOuterCircleRadius();  // calculate the smallest outer circle (simple version)
$radius=$polygon->largestInnerCircleRadius();   // calculate the smallest inner circle (simple version)

$polygon2=$polygon->expand(10);                 // expand (blow up) the polygon by 10 meters
$polygon2=$polygon->simplify(0.001,true);       // simplify the polygon by removing coordinates but keeping shape
```




