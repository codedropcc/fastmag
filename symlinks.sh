cd $1
find * -type l -not -exec grep -q "^{}$" .gitignore \; -print >> .gitignore; cat .gitignore | sort | uniq > temp.gitignore; mv .gitignore .gitignore.bk; mv temp.gitignore .gitignore; rm -f .gitignore.bk; rm -f temp.gitignore
