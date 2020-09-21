all: vm

# Reinstall the VM
reinstall: clean vm

# Clean everything to pristine conditions
clean:
	# Destroy the VM and delete .vagrant
	vagrant destroy --force
	rm -rf .vagrant

	# Remove ansible roles downloaded from galaxy
	rm -rf provisioning/roles/geerlingguy.*

# Create the VM
vm:

	# Download ansible roles
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.php
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.nginx
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.certbot

	# Spin up the machine
	vagrant up

provision:
	# Bring up the machine if it is not already
	vagrant up

	# Run all content provisioners
	vagrant provision

site:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for content update
	vagrant provision --provision-with site

teeworlds:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for content update
	vagrant provision --provision-with teeworlds

build:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for teeworlds rebuild
	vagrant provision --provision-with build
