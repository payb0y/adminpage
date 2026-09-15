import axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";

function pdfUrl(organizationId) {
  return generateUrl(
    `/apps/projectcreatoraio/api/v1/organizations/${organizationId}/default-pdf`,
  );
}

function documentTypesUrl(organizationId, documentTypeId) {
  const suffix = documentTypeId ? `/${documentTypeId}` : "";
  return generateUrl(
    `/apps/projectcreatoraio/api/v1/organizations/${organizationId}/ocr/document-types${suffix}`,
  );
}

const requestConfig = {
  headers: {
    "OCS-APIRequest": "true",
  },
};

export async function listOrganizationTeams(organizationId) {
  const response = await axios.get(
    generateUrl(`/ocs/v2.php/apps/organization/organizations/${organizationId}/teams`),
    { ...requestConfig, params: { format: "json" } },
  );
  const ocsData = response.data && response.data.ocs && response.data.ocs.data;
  return (ocsData && ocsData.teams) || [];
}

export async function assignProjectTeam(organizationId, projectId, teamId) {
  const response = await axios.put(
    generateUrl(
      `/ocs/v2.php/apps/organization/organizations/${organizationId}/projects/${projectId}/team`,
    ),
    { teamId: teamId == null ? null : Number(teamId) },
    { ...requestConfig, params: { format: "json" } },
  );
  return response.data;
}

export async function getOrganizationPdfInfo(organizationId) {
  const response = await axios.get(pdfUrl(organizationId), requestConfig);
  return response.data;
}

export async function uploadOrganizationPdf(organizationId, file, fileName) {
  const formData = new FormData();
  formData.append("pdf", file);
  formData.append("fileName", fileName);

  const response = await axios.post(pdfUrl(organizationId), formData, requestConfig);
  return response.data;
}

export async function deleteOrganizationPdf(organizationId) {
  const response = await axios.delete(pdfUrl(organizationId), requestConfig);
  return response.data;
}

export async function listOrganizationDocumentTypes(organizationId) {
  const response = await axios.get(documentTypesUrl(organizationId), {
    ...requestConfig,
    params: { include_inactive: 1 },
  });
  return (response.data && response.data.document_types) || [];
}

export async function createOrganizationDocumentType(organizationId, payload) {
  const response = await axios.post(documentTypesUrl(organizationId), payload, requestConfig);
  return response.data;
}

export async function updateOrganizationDocumentType(organizationId, documentTypeId, payload) {
  const response = await axios.put(
    documentTypesUrl(organizationId, documentTypeId),
    payload,
    requestConfig,
  );
  return response.data;
}

export async function deleteOrganizationDocumentType(organizationId, documentTypeId) {
  const response = await axios.delete(
    documentTypesUrl(organizationId, documentTypeId),
    requestConfig,
  );
  return response.data;
}
