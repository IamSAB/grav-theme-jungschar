function initMap() {

  // the map
  const map = new google.maps.Map(document.getElementById("map"), {
    zoom: 3,
    center: { lat: -28.024, lng: 140.887 },
  });

  google.maps.event.addListenerOnce(map, 'bounds_changed', function () { 
    this.setZoom(Math.min(15, this.getZoom())); 
  });

  // the map bounds (for auto-centering around markers)
  const bounds = new google.maps.LatLngBounds();

  // the info window (for later use)
  const infoWindow = new google.maps.InfoWindow({
    content: "",
    disableAutoPan: true,
  });

  // Add some markers to the map.
  const markers = Object.entries(locations).map(([adr, loc]) => {

    console.log(loc);

    // coordinates
    var latLng = new google.maps.LatLng(loc.lat, loc.lng);

    // marker itself
    const marker = new google.maps.Marker({
      position: latLng,
      title: adr,
      map: map
    });

    // consider marker in bounds
    bounds.extend(marker.position);

    // markers can only be keyboard focusable when they have click listeners
    // open info window when marker is clicked
    marker.addListener("click", () => {
      var content = `<div><i>${loc.title}</i><br><b>${adr}</b>`;
      if ('date' in loc) {
        content += `<br>${loc.date}`;
      }
      content += '</div>';
      infoWindow.setContent(content);
      infoWindow.open(map, marker);
    });

    return marker;
  });

  // fit map to set bounds
  map.fitBounds(bounds);
}
