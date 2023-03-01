#
#    Ophidia Server
#    Copyright (C) 2012-2022 CMCC Foundation
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#

#!/bin/bash

# Input parameters
taskid=${1}
ncores=${2}
log=${3}
submissionstring=${4}
queue=${5}
serverid=${6}
workflowid=${7}
project=${8}
taskname=${9}

# Base path
OPH_BASE=/usr/local/ophidia
SLURM_BASE=/usr/local/ophidia/extra

# Const
fixString=
FRAMEWORK_PATH=${OPH_BASE}/oph-cluster/oph-analytics-framework
LAUNCHER=${SLURM_BASE}/bin/srun
JOBNAME="${taskname} ${fixString}${serverid}${taskid}"
SCRIPT_DIR=${HOME}/.ophidia
SCRIPT_FILE=${SCRIPT_DIR}/${serverid}${taskid}.submit.sh

# Body
mkdir -p ${SCRIPT_DIR}
> ${SCRIPT_FILE}
echo "#!/bin/bash" >> ${SCRIPT_FILE}
echo "${FRAMEWORK_PATH}/bin/oph_analytics_framework \"${submissionstring}\"" >> ${SCRIPT_FILE}
chmod +x ${SCRIPT_FILE}

MPI_TYPE=--mpi=pmi2
if [ ${ncores} -eq 1 ]
then
	if [[ ${submissionstring} = *"operator=oph_script;"* || ${submissionstring} = *"operator=oph_generic;"* || ${submissionstring} = *"operator=oph_cdo;"* ]]
	then
		MPI_TYPE=--mpi=none
	fi
fi

${LAUNCHER} ${MPI_TYPE} --input=none -n ${ncores} -o ${log} -e ${log} -J "${JOBNAME}" ${SCRIPT_FILE}
if [ $? -ne 0 ]; then
	echo "Unable to submit ${SCRIPT_FILE}"
	rm ${SCRIPT_FILE}
	exit -1
fi

rm ${SCRIPT_FILE}

exit 0

