	<?php if(USE_LESS): ?>
		<script>
			less = {
				env: "development",
				async: false,
				fileAsync: false,
				poll: 100,
				functions: {},
				dumpLineNumbers: "comments",
				relativeUrls: false,
				rootpath: ":/a.com/"
			};
		</script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js"></script>
		<script>less.watch();</script>
	<?php endif; ?>
	<script src="<?= URL_JS; ?>script.js"></script>
</body>
</html>