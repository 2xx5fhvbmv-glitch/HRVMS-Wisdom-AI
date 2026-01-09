<!DOCTYPE html>
<html>
<head>
  <title>HRVMS-WisdomAI</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
  <style>
    @import url("https://p.typekit.net/p.css?s=1&k=jaa4rhc&ht=tk&f=15528.15529.15530.17251.17252.17253&a=32242626&app=typekit&e=css");
    @font-face {
      font-family: "europa";
      src: url("https://use.typekit.net/af/f3ba4f/00000000000000003b9b12fa/27/l?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n7&v=3") format("woff2"), url("https://use.typekit.net/af/f3ba4f/00000000000000003b9b12fa/27/d?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n7&v=3") format("woff"), url("https://use.typekit.net/af/f3ba4f/00000000000000003b9b12fa/27/a?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n7&v=3") format("opentype");
      font-display: auto;
      font-weight: 700;
    }
    @font-face {
      font-family: "europa";
      src: url("https://use.typekit.net/af/3e64fb/00000000000000003b9b12fe/27/l?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n3&v=3") format("woff2"), url("https://use.typekit.net/af/3e64fb/00000000000000003b9b12fe/27/d?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n3&v=3") format("woff"), url("https://use.typekit.net/af/3e64fb/00000000000000003b9b12fe/27/a?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n3&v=3") format("opentype");
      font-display: auto;
      font-style: normal;
      font-weight: 300;
    }
    @font-face {
      font-family: "europa";
      src: url("https://use.typekit.net/af/4eabcf/00000000000000003b9b12fd/27/l?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n4&v=3") format("woff2"), url("https://use.typekit.net/af/4eabcf/00000000000000003b9b12fd/27/d?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n4&v=3") format("woff"), url("https://use.typekit.net/af/4eabcf/00000000000000003b9b12fd/27/a?primer=7cdcb44be4a7db8877ffa5c0007b8dd865b3bbc383831fe2ea177f62257a9191&fvd=n4&v=3") format("opentype");
      font-display: auto;
      font-style: normal;
      font-weight: 400;
    }
    @font-face {
      font-family: 'Eagle-Light';
      src: url('../fonts/Eagle-Light.eot?#iefix') format('embedded-opentype'), url('../fonts/Eagle-Light.woff') format('woff'), url('../fonts/Eagle-Light.ttf') format('truetype'), url('../fonts/Eagle-Light.svg#Eagle-Light') format('svg');
      font-weight: normal;
      font-style: normal;
    }

    body{background-color: #F6F6F6;font-family: "europa", sans-serif;font-size:15px;font-weight:400;color:#24303f;}
    a {color:#45747a;}
    a:hover{text-decoration:none;}
    .myappoint-contain .logo{width:300px;margin-bottom:50px;text-align:center;}
    .myappoint-contain div{text-align:center;}
    .find-logo{margin-bottom:70px;}
    .find-logo img{max-width: 350px;}
    .myappoint-contain{padding:20px 50px;box-shadow:0px 0px 2px #acacac;background:white;margin:85px 0;border-radius:8px;font-family:"europa", sans-serif;}
    .myappoint-contain p,.myappoint-contain h5{font-size:20px;font-weight:300;text-align:left;margin:12px 0;font-family:"europa", sans-serif;}
    .myappoint-contain p span{font-weight:300;}
    .myappoint-contain p a{color:#45747a;margin-left:10px;font-weight:normal;}
    .myappoint-contain ul{text-align:left;font-size:20px;}
    .myappoint-contain .button{background:#67ADB5;padding:5px 45px;border-radius:200px;color:white;font-weight:500;margin-top:25px;display:inline-block;}
    .icon ul{justify-content:center;margin-top:45px;}
    .icon ul li a{border-radius: 100%;width: 40px;height:40px;display: inline-flex;align-items: center;justify-content: center;color: white;margin: 10px;}
    .icon ul li a:hover{color: white;text-decoration: none;}
    .icon a{color: #24303f;font-size: 20px;font-weight: 300;}
    .icon ul li a img{max-height: 40px;}
    .send-via img{max-width: 325px;float: right;}
    @media only screen and (max-width: 425px) {
      img {max-width: 100% !important;height:auto;}
      .myappoint-contain .logo{width: 220px;}
      .myappoint-contain p{font-size: 16px;}
      .myappoint-contain .button{padding: 5px 40px;}
    }
    @media only screen and (max-width: 600px) {
      .myappoint-contain{padding: 20px 20px;margin:20px 0;}    
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="row d-flex justify-content-center" style="width:100%;box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);border:1px solid #2121246b;padding:10px;">
      <div class=" col-lg-6 col-md-8 myappoint-contain " style="background-color:white">
        <div class="find-logo" style="text-align: center;" >
          <img src="{{ URL::asset('assets/images/header_logo.png') }}" style="max-width: 185px;"/>
        </div>