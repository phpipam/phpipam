#!/bin/bash

set -e

ENVIRONMENT=""
ENV=${ENV:-"dev"}
case "$ENV" in
    "dev") 
        export ENVIRONMENT="aws42-istio-networking/phpipam-dev" 
        export VAULT_KV_PATH="network-engineering/phpipam/dev"
        ;;
    "prod") 
        export ENVIRONMENT="aws42-istio-networking/phpipam-prod" 
        export VAULT_KV_PATH="network-engineering/phpipam/prod"
        ;;
esac

export CLUSTER=`echo $ENVIRONMENT | cut -d/ -f1`
export NAMESPACE=`echo $ENVIRONMENT | cut -d/ -f2`
export ENV=`echo $NAMESPACE | awk -F'-' '{print $NF}'`
GIT_REF=${GIT_REF:-"$GITHUB_REF"}
GIT_COMMIT=${GIT_COMMIT:-"$GITHUB_SHA"}
PIPELINE=${PIPELINE:-"poc-vault"}

jq ".API.Pipeline=\"$PIPELINE\"|.Git.Ref=\"$GIT_REF\"|.Git.Revision=\"$GIT_COMMIT\"|.Image.Tag=\"$GIT_COMMIT\"|.Config.VaultKVPath=\"$VAULT_KV_PATH\"|.K8S.ClusterName=\"$CLUSTER\"|.K8S.Namespace=\"$NAMESPACE\"" \
    devops/tekton/trigger.json > trigger.json

cat trigger.json

export EVENT_ID=`curl http://el-build-pipeline-listener.tekton-pipelines.svc.cluster.local:8080/v1/$PIPELINE \
    -d @trigger.json | jq -r '.eventID'`

echo Execution Logs: https://tekton.devops.umgapps.com/#/pipelineruns?labelSelector=triggers.tekton.dev%2Ftriggers-eventid%3D$EVENT_ID

tknwatch

jq ".API.Pipeline=\"$PIPELINE\"|.Git.Ref=\"$GIT_REF\"|.Git.Revision=\"$GIT_COMMIT\"|.Image.Tag=\"$GIT_COMMIT\"|.Config.VaultKVPath=\"$VAULT_KV_PATH\"|.K8S.ClusterName=\"$CLUSTER\"|.K8S.Namespace=\"$NAMESPACE\"" \
    devops/tekton/trigger-cron.json > trigger-cron.json

cat trigger.json

export EVENT_ID=`curl http://el-build-pipeline-listener.tekton-pipelines.svc.cluster.local:8080/v1/$PIPELINE \
    -d @trigger-cron.json | jq -r '.eventID'`

echo Execution Logs: https://tekton.devops.umgapps.com/#/pipelineruns?labelSelector=triggers.tekton.dev%2Ftriggers-eventid%3D$EVENT_ID

tknwatch
