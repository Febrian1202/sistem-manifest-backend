.PHONY: help package

help: ## Tampilkan semua target
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

package: ## Buat zip bersih (semua file tracked git) di dist/
	@mkdir -p dist
	@git status --porcelain | grep -q . && echo "WARNING: ada uncommitted changes, hanya HEAD yang di-zip" || true
	@git archive --worktree-attributes --format=zip --prefix=sistem-manifest/ -o dist/sistem-manifest-clean-$$(date +%Y%m%d-%H%M%S).zip HEAD
	@ls -lh dist/*.zip | tail -1
