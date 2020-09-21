# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile config version 2
Vagrant.configure("2") do |config|
  # Use a CENTOS 8 Linux environment
  config.vm.box = "geerlingguy/debian10"
  config.vm.hostname = "teeworlds"

  # Install required Vagrant plugins
  #   vagrant-vbguest:     to automatically install guest additions in the vm
#  config.vagrant.plugins = "vagrant-vbguest"

  # Allow vagrant-vbguest to search for the latest kernel update
  # It's desirable and fixes a crash where the plugin tries to access a nonexistant repo
# config.vbguest.installer_options = { allow_kernel_upgrade: true  }

  # Disable the default vagrant share
  config.vm.synced_folder ".", "/vagrant", type: "rsync", disabled: true

  # Forward ports
  config.vm.network "forwarded_port", guest: 443,  host: 4430, protocol: "tcp"
  config.vm.network "forwarded_port", guest: 80,   host: 8080, protocol: "tcp"
  config.vm.network "forwarded_port", guest: 8303, host: 8303, protocol: "udp"
  config.vm.network "forwarded_port", guest: 8303, host: 8303, protocol: "tcp"

  # Use VirtualBox
  config.vm.provider "virtualbox" do |vb|
    vb.name = "VAGRANT Teeworlds test server"

    # Allocate 4GB to VB
    vb.memory = 1024

    # Allocate 4 V-Cores to VB
    vb.cpus = 2
  end

  # Complete ansible provisioning
  config.vm.provision "provision", type: "ansible" do |provision|
    provision.playbook = "provisioning/playbook.yml"
  end

  # Site provisioning
  config.vm.provision "site", type: "ansible" do |site|
    site.playbook = "provisioning/playbook.yml"
    site.tags = "site"
  end

  # Teeworlds provisioning
  config.vm.provision "teeworlds", type: "ansible" do |teeworlds|
    teeworlds.playbook = "provisioning/playbook.yml"
    teeworlds.tags = "teeworlds"
    teeworlds.verbose = "vv"
  end

  # Certbot provisioning
  config.vm.provision "certbot", type: "ansible" do |certbot|
    certbot.playbook = "provisioning/playbook.yml"
    certbot.tags = "teeworlds"
    certbot.verbose = "vv"
  end

  # Build provisioning
  config.vm.provision "build", type: "ansible" do |build|
    build.playbook = "provisioning/playbook.yml"
    build.tags = "build"
    build.verbose = "vv"
  end

  # Setup the greeting message
  config.vm.post_up_message = <<-END
  TEST MESSAGE.
  IN THE FUTURE USEFUL INFORMATION WILL BE STORED HERE.
  ASD ASD ASD ASD ASD ASD ASD ASD ASD ASD ASD ASD ASD ASD
  Let's see if i can reference variable names: #{config.vm.hostname}
  END
end
