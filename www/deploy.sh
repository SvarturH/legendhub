#!/bin/bash

#declare variables
configCreated=n
deployDir=""

#Read conf file
confCount=$((0))
if [ -f "deploy.sh.conf" ]; then
    while IFS='' read -r line || [[ -n "$line" ]]; do
        case $confCount in
            0)
                configCreated=$line
                ;;
            1)
                deployDir=$line
                ;;
            *)
                ;;
        esac
        confCount=$(($confCount+1))
    done < "deploy.sh.conf"
fi

#Reminders
msg="Have you created your php config?"
if [[ $configCreated != ""  ]]; then
    msg+="[$configCreated]: "
else
    msg+=": "
fi

read -p "$msg" input
if [[ $input = "" ]]; then
    if [[ $configCreated != "" ]]; then
        input=$configCreated
    fi
else
    configCreated=$input
fi

if [[ $input == "" || ($input != "y" && $input != "Y") ]]; then
	echo "Please create your php config file using the template provided."
	exit 1
fi

# Choose directory
msg="Enter deploy directory"
if [[ $deployDir != "" ]]; then
    msg+="[$deployDir]: "
else
    msg+=": "
fi

input=""
while : ; do
    read -p "$msg" -e input
    [[ $input == "" && $deployDir == "" ]] || break
done

if [[ $input == "" ]]; then
    if [[ $deployDir != "" ]]; then
        input=$deployDir
    fi
else
    deployDir=$input
fi

#save config
echo "$configCreated" > "deploy.sh.conf"
echo "$deployDir" >> "deploy.sh.conf"

# Move files to bin folder to work on
if [[ -d "bin" ]]; then
	rm -rf bin
fi
mkdir bin

cp -r .htaccess builder changelog css items maintenance.html php wiki error index.html js login.html mobs quests shared bin/

echo
echo "Copied necessary files to bin folder"

# Copy to publish path
if ! [[ -d $deployDir ]]; then
	mkdir $deployDir
fi

cp -r bin/. $deployDir
echo
echo "Deployed files from bin/. to $deployDir"