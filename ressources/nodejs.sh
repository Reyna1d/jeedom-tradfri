#!/bin/bash
cd $1
touch /tmp/tradfri_dep
echo "Début de l'installation"

echo 0 > /tmp/tradfri_dep
DIRECTORY="/var/www"
if [ ! -d "$DIRECTORY" ]; then
  echo "Création du home www-data pour npm"
  sudo mkdir $DIRECTORY
fi
sudo chown -R www-data $DIRECTORY
echo 10 > /tmp/tradfri_dep
actual=`nodejs -v`;
echo "Version actuelle : ${actual}"

if [[ $actual == *"4."* || $actual == *"5."* || $actual == *"6."* || $actual == *"7."* || $actual == *"8."*]]
then
  echo "Ok, version suffisante";
else
  echo "KO, version obsolète à upgrader";
  echo "Suppression du Nodejs existant et installation du paquet recommandé"
  sudo apt-get -y --purge autoremove nodejs npm
  arch=`arch`;
  echo 20 > /tmp/tradfri_dep

  if [[ $arch == "armv6l" ]]
  then
    echo "Raspberry 1 détecté, utilisation du paquet pour armv6"
    sudo rm /etc/apt/sources.list.d/nodesource.list
    wget http://node-arm.herokuapp.com/node_latest_armhf.deb
    sudo dpkg -i node_latest_armhf.deb
    sudo ln -s /usr/local/bin/node /usr/local/bin/nodejs
    rm node_latest_armhf.deb
  fi

  if [[ $arch == "aarch64" ]]
  then
    echo "Architecture 64bits détecté, utilisation du paquet pour arm64"
    wget http://dietpi.com/downloads/binaries/c2/nodejs_5-1_arm64.deb
    sudo dpkg -i nodejs_5-1_arm64.deb
    sudo ln -s /usr/local/bin/node /usr/local/bin/nodejs
    rm nodejs_5-1_arm64.deb
  fi

  if [[ $arch != "aarch64" && $arch != "armv6l" ]]
  then
    echo "Utilisation du dépot officiel"
    curl -sL https://deb.nodesource.com/setup_5.x | sudo -E bash -
    sudo apt-get install -y nodejs
  fi
  
  new=`nodejs -v`;
  echo "Version actuelle : ${new}"
fi

cd ../node/

# MAJ des droits d'exec des bin coaps
echo 30 > /tmp/tradfri_dep
cd bin
echo "MAJ des droits sur les clients COAP"
chmod +x coap-client-linux
chmod +x coap-client-linux-32
chmod +x coap-client-darwin 
cd ..

echo 40 > /tmp/tradfri_dep
echo "Clean npm"
npm cache clean
sudo npm cache clean
sudo rm -rf node_modules

echo 50 > /tmp/tradfri_dep
echo "Installation de simple-node-logger"
sudo npm install simple-node-logger

echo 60 > /tmp/tradfri_dep
echo "Installation de d3-queue"
sudo npm install d3-queue

echo 70 > /tmp/tradfri_dep
echo "Installation de request"
sudo npm install request

echo 80 > /tmp/tradfri_dep
echo "Installation de colors"
sudo npm install colors

echo 90 > /tmp/tradfri_dep
sudo chown -R www-data *


rm /tmp/tradfri_dep

echo "Fin de l'installation"
