$json = $input -join "`n" | ConvertFrom-Json
$path = $json.tool_input.file_path
if ($path -match 'routes[\\/]web|Controllers[\\/]|migrations[\\/]') {
    Write-Output "📝 [README] שינוי בנתיב קריטי — אם נוסף או שונה פיצ'ר, לעדכן גם README.md"
}
