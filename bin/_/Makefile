#
# WARNING
#	DO NOT USE, NOT YET ADAPTED TO PAYMENTDEV
#
#	make lint
#	make test TARGET=PHP5 VERBOSE=1
#	make doc
#
# SVN: $Id: Makefile 282 2008-02-14 16:39:59Z ptran $


## Options

VERBOSE?= 	0

LOCAL_BIN=	/usr/local/bin

## Commands

SHELL=		sh
SED=		sed
DOXYGEN=	doxygen
SVN=		svn
PHP=		php

## Dirs (XXX need to normalize this...)

TEST_DIR=	t
BUILD_DIR=	$(PWD)/build
DOC_DIR=	$(PWD)/build/doc
LOG_DIR=	$(PWD)/build/log

## Doxygen Setup

DOXCONF_IN=	$(PWD)/DOXYGEN.in
DOXCONF=	$(BUILD_DIR)/DOXYGEN

## PHP Setup

PHP_LOG=	$(LOG_DIR)/php.log

PHP_INI=	$(PWD)/etc/php.ini
PHP_LIBS=	$(PWD)/src/Narvalo
PHP_ARGS=	-c $(PHP_INI) \
		--define include_path=$(PHP_LIBS) \
		--define error_log=$(PHP_LOG)
PHP_EXEC= 	$(PHP) $(PHP_ARGS)
PHP_EXTS=	php t

## Exuberant Tags

TAGS=		$(PWD)/TAGS

## Miscs

ifeq ($(VERBOSE), 0)
	PROVE=	$(LOCAL_BIN)/prove -Q
	LINT=	lint-quiet
	CTAGS=	/usr/bin/exctags
else
	PROVE=	$(LOCAL_BIN)/bin/prove
	LINT=	lint-verbose
	CTAGS=	/usr/bin/exctags -V
endif

## Rules

all: version lint test
	@echo
	@echo	"Tests complete."
	@echo

version:
	@echo
	@echo 	"Smoke tests..."
	@echo

lint: lint-pre $(LINT) lint-post

test: 
	@echo 	"Harness..."
	@mkdir -p $(LOG_DIR)					;\
	$(PROVE) --exec '$(PHP_EXEC)' -r $(TEST_DIR)

doc:
	@echo	"Building doc..."
	@mkdir -p $(DOC_DIR)					;\
	mkdir -p $(LOG_DIR)					;\
	sed -e 's|@OUTDIR@|$(DOC_DIR)|' 		 	 \
		-e 's|@LOGFILE@|$(LOG_DIR)/dox.log|' 	 	 \
		-e 's|@SRCDIR@|$(PWD)/src|' 		 	 \
		-e 's|@PROJECT_NAME@|Meetic Billing|'	 	 \
		-e 's|@EXCLUDE@|vendor|'	 	 	 \
		-e 's|@EXCLUDE_PATTERNS@|*/vendor/*|'	 	 \
			$(DOXCONF_IN) > $(DOXCONF)     		;\
	$(DOXYGEN) $(DOXCONF)
	@echo 	"Complete."

update:
	@$(SVN) update

tags: clean-tags
	@echo 	"Tagging..."
	@$(CTAGS) --file-scope=no --langmap=php:+.inc,php:+.phpclass -R libs
	@echo	"Complete."

bench:
	@echo	"Not yet done..."

.PHONY: clean
clean: clean-svn
	@rm -rf $(BUILD_DIR)

clean-log:
	@find $(LOG_DIR) -name \*.log | xargs rm -f

clean-doc:
	@rm -rf $(DOC_DIR)

clean-tags:
	@rm -f $(TAGS)

clean-svn:
	@$(SVN) cleanup
	@find . -name svn-commit.tmp -type f | xargs rm -f


## Internal rules

lint-pre:
	@echo	"Lint..."
	@mkdir -p $(LOG_DIR)

lint-post:
	@echo 	"Result: PASS"
	@echo

lint-verbose:
	@for ext in $(PHP_EXTS); do					  \
		echo "Extension *.$$ext..."                              ;\
		find . -name \*.$$ext -type f | xargs -n1 $(PHP_EXEC) -l ;\
		if [ $$? -ne 0 ]; then exit 1; fi			 ;\
	done

lint-quiet:
	@errnum=0							 ;\
	for ext in $(PHP_EXTS); do					  \
		find $(PHP_LIBS) -name \*.$$ext -type f -exec $(PHP_EXEC) -l {} \;  \
			| grep '^Errors parsing' 			 ;\
		if [ $$? -eq 0 ]; then let "errnum += 1"; fi 		 ;\
	done								 ;\
	if [ $$errnum -ne 0 ]; then exit 1; fi

