{{--
    Google Maps JavaScript API dynamic library loader (official inline
    bootstrap). Only rendered when GOOGLE_MAPS_BROWSER_KEY is set. The key is
    a browser key and is public by design — restrict it in Google Cloud to
    this site's HTTP referrers and to Maps JavaScript API + Places API (New).

    The Places library is imported as soon as the page scripts start; the
    location fields stay disabled until it has loaded successfully.
--}}
@props(['apiKey'])

@once
    <script>
        (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({key: @json($apiKey), v: "weekly", region: "IN", language: "en"});

        // Called by Google when the key is invalid or not authorised for this site.
        // The flag persists in case this fires before the page scripts start;
        // location search then stays disabled (see place-autocomplete.js).
        window.gm_authFailure = () => {
            window.__h2PlacesAuthFailed = true;
            window.dispatchEvent(new CustomEvent('places:auth-failure'));
        };
    </script>
@endonce
