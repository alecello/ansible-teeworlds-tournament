all: vm

# Reinstall the VM
reinstall: clean vm

clean:
	# Clean everything to pristine conditions
	# Destroy the VM and delete .vagrant
	vagrant destroy --force
	rm -rf .vagrant

	# Remove ansible roles downloaded from galaxy
	rm -rf provisioning/roles/geerlingguy.*

	# Remove cloned directories
	rm -rf provisioning/files/phpmailer
	rm -rf provisioning/files/teeworlds/teeworlds

vm:
	# Create the VM
	# Download ansible roles
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.php
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.nginx
	ansible-galaxy install --roles-path provisioning/roles geerlingguy.certbot

	# Spin up the machine
	vagrant up

provision:
	# Bring up the machine if it is not already
	vagrant up

	# Run all provisioners
	vagrant provision

site:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for content update
	vagrant provision --provision-with site

teeworlds:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for teeworlds
	vagrant provision --provision-with teeworlds

certbot:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for certbot certificate renewal
	vagrant provision --provision-with certbot

build:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for teeworlds rebuild
	vagrant provision --provision-with build

db:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner for database
	vagrant provision --provision-with db

deploy:
	ansible-playbook -i provisioning/hosts provisioning/playbook.yml