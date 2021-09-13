.PHONY: run
run:
	docker run -it \
		--env HCLOUD_TOKEN \
		quay.io/luzilla/hcloud-housekeeping:latest
