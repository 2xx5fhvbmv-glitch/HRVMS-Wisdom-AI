{{--
    Country-name -> ISO code lookup + real flag images for Talent Acquisition screens.
    Included inside an existing <script> block. The page only carries the country NAME, not a
    flag URL, so the code is looked up here; unknown names show no flag. Flags use the same
    flagcdn.com PNGs the app already stores in countries.flag_url, and the local Maldives asset.
    If an image fails to load it falls back to a 2-letter code chip.
--}}
const TA_COUNTRY_CODES = {Afghanistan:'AF',Albania:'AL',Algeria:'DZ',Argentina:'AR',Australia:'AU',Austria:'AT',Bangladesh:'BD',Belgium:'BE',Bhutan:'BT',Brazil:'BR',Bulgaria:'BG',Cambodia:'KH',Canada:'CA',China:'CN',Colombia:'CO',Croatia:'HR','Czech Republic':'CZ',Denmark:'DK',Egypt:'EG',Ethiopia:'ET',Fiji:'FJ',Finland:'FI',France:'FR',Germany:'DE',Ghana:'GH',Greece:'GR',Hungary:'HU',India:'IN',Indonesia:'ID',Iran:'IR',Iraq:'IQ',Ireland:'IE',Italy:'IT',Japan:'JP',Jordan:'JO',Kenya:'KE',Kuwait:'KW',Lebanon:'LB',Madagascar:'MG',Malaysia:'MY',Maldives:'MV',Mauritius:'MU',Mexico:'MX',Morocco:'MA',Myanmar:'MM',Nepal:'NP',Netherlands:'NL','New Zealand':'NZ',Nigeria:'NG',Norway:'NO',Oman:'OM',Pakistan:'PK',Peru:'PE',Philippines:'PH',Poland:'PL',Portugal:'PT',Qatar:'QA',Romania:'RO',Russia:'RU','Saudi Arabia':'SA',Serbia:'RS',Seychelles:'SC',Singapore:'SG','South Africa':'ZA','South Korea':'KR',Spain:'ES','Sri Lanka':'LK',Sweden:'SE',Switzerland:'CH',Syria:'SY',Thailand:'TH',Tunisia:'TN',Turkey:'TR',Uganda:'UG',Ukraine:'UA','United Arab Emirates':'AE','United Kingdom':'GB','United States':'US',Uzbekistan:'UZ',Vietnam:'VN',Zimbabwe:'ZW'};
const TA_MV_FLAG = @json(URL::asset('resorts_assets/images/flag-maldives.webp'));
const taFlag = c => {
    if (!c) return '';
    const src = c === 'MV' ? TA_MV_FLAG : 'https://flagcdn.com/w40/' + c.toLowerCase() + '.png';
    return `<img class="flagimg" src="${src}" alt="${c}" loading="lazy" data-c="${c}" onerror="this.outerHTML='<span class=&quot;ccode&quot;>'+this.dataset.c+'</span>'">`;
};
