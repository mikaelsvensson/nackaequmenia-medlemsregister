(function () {
    var onLoad = function () {
        var logContainer = document.getElementById('log');
        var log = function (msg) {
            var el = document.createElement('p');
            el.innerHTML = msg;
            logContainer.insertBefore(el, logContainer.firstChild);
        };
        document.getElementById('startTracking').addEventListener(
            'click',
            function () {
                if (navigator.geolocation) {
                    var mymap = L.map('mapid').setView([51.505, -0.09], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 18
                    }).addTo(mymap);

                    navigator.geolocation.watchPosition(
                        function (position) {
                            log('We got something: long ' + position.coords.longitude + ' lat ' + position.coords.latitude + ' +/- ' + position.coords.accuracy + ' meters');
                            mymap.setView([position.coords.latitude, position.coords.longitude], 13);
                        },
                        function (positionError) {
                            log('Oops, something went wrong: ' + positionError.code + ' ' + positionError.message);
                        },
                        {
                            enableHighAccuracy: false,
                            // timeout: 5000,
                            maximumAge: 10 * 1000
                        }
                    )
                }
                log('Let us get started');
            });
    };
    window.addEventListener('load', onLoad)
})();
