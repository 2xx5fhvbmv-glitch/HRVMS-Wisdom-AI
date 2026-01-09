<footer class="main-footer">
	<strong>Copyright &copy; <span id="copyright-year"></span> <a href="{{route('admin.dashboard')}}">HRVMS-WisdomAI</a>.</strong>
	All rights reserved.
	<div class="float-right d-none d-sm-inline-block">
		<b>Version</b> 1.0.0
	</div>
	<script>
		document.getElementById("copyright-year").innerText = new Date().getFullYear();
	</script>
</footer>
