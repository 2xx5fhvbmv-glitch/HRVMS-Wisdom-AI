	<footer style="text-align:center">
		@php
			$__footerBrand = $resortName ?? null;
			if (empty($__footerBrand)) {
				$__resortUser = auth('resort-admin')->user() ?? auth('api')->user() ?? null;
				if ($__resortUser && !empty($__resortUser->resort_id)) {
					$__footerBrand = optional($__resortUser->resort)->resort_name;
				}
			}
		@endphp
		<strong>Copyright &copy; <span id="copyright-year"></span>
			<a style="color:blue">{{ $__footerBrand ?: 'HRVMS-WisdomAI' }}</a>.
		</strong>
	</footer>
</body>
</html>