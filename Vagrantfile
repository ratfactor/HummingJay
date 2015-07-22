Vagrant.configure(2) do |config|
  config.vm.box = "hashicorp/precise32"
  config.vm.network "forwarded_port", guest: 80, host: 8787

  config.vm.provision "shell", inline: <<-SHELL
    sudo apt-get upgrade
    sudo apt-get update
    sudo apt-get install -y python-software-properties
    sudo add-apt-repository -y ppa:ondrej/php5-5.6
    sudo apt-get update
    sudo apt-get install -y apache2 php5
    sudo wget https://phar.phpunit.de/phpunit.phar
    sudo chmod +x phpunit.phar
    sudo mv phpunit.phar /usr/local/bin/phpunit
    if ! [ -L /var/www ]; then
      rm -rf /var/www/html
      ln -fs /vagrant/demo /var/www/html
    fi    
  SHELL
end
