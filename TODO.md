# TODO

- Clarify why the parameter in apiClient('XXLNC') comes from a configurable setting.
- Create .pot file when finished
- Ensure the API package logs failures, or determine if this package should handle logging failures itself?
-> for now te debug log of GF is used. Which should be enabled. Think about a proper solution and probably replace GF log error functions.
- Add RSIN setting and use in php-di.php (zgw.rsin).
- Add depedency on idp-userdata when the package supports php8.
- Should we create 'actions' per client or could we just use one? So it applies to all clients?
- refactor all the dockblocks, @throws and @since.
- failed messages texts, refactor for sure!
