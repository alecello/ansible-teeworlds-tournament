default: vm

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

content:
	# Bring up the machine if it is not already
	vagrant up

	# Run the content provisioner
	vagrant provision --provision-with content