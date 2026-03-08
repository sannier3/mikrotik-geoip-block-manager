#!/usr/bin/env bash
set -euo pipefail

# ============================================================
# Generate MikroTik .rsc geo-block / geo-observe script
# Source: ipverse/country-ip-blocks
# IPv4 + IPv6
#
# BLOCK:
#   - address-lists: geo-country-xx-v4 / v6
#   - rules: raw prerouting drop
#
# OBSERVE:
#   - address-lists: geo-country-xx-v4 / v6
#   - rules: mangle prerouting passthrough
#   - counts only incoming NEW connections on WAN
# ============================================================

MODE="${MODE:-replace}"                    # replace | keep
VERBOSE="${VERBOSE:-1}"                    # 0 silent, 1 steps, 2 details
WAN_LIST="${WAN_LIST:-WAN}"
OUTPUT="${OUTPUT:-geo-policy-ipverse.rsc}"

PURGE_RULES_ONLY="${PURGE_RULES_ONLY:-yes}"    # yes | no
PURGE_ONLY="${PURGE_ONLY:-no}"                 # yes | no
OBSERVE_ALL_OTHERS="${OBSERVE_ALL_OTHERS:-yes}" # yes | no

BASE_RAW_URL="${BASE_RAW_URL:-https://raw.githubusercontent.com/ipverse/country-ip-blocks/master/country}"
BASE_API_URL="${BASE_API_URL:-https://api.github.com/repos/ipverse/country-ip-blocks/contents/country}"

# Pays bloques par defaut
if [ "$#" -gt 0 ]; then
  BLOCK_COUNTRIES=("$@")
else
  BLOCK_COUNTRIES=(
    ru cn ir kp by
    vn hk bd id
    mx br ar ec
    dz ao bj bw bf bi cm cv cf td km cg cd ci dj eg gq er et ga gm gh gn gw ke ls lr ly mg mw ml mr mu ma mz na ne ng rw st sn sc sl so za ss sd sz tz tg tn ug eh zm zw
    af am az bh bn bt ge iq jo kg kh kw kz la lb lk mm mn mo np om ph pk ps qa sa sy tj tl tm uz ye
    bo cl co fk gf gy pe py sr uy ve ae al au bg cu ee fj fm il in
  )
fi

WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT

TOTAL_BLOCK_V4_LISTS=0
TOTAL_BLOCK_V6_LISTS=0
TOTAL_OBS_V4_LISTS=0
TOTAL_OBS_V6_LISTS=0
TOTAL_BLOCK_V4_PREFIXES=0
TOTAL_BLOCK_V6_PREFIXES=0
TOTAL_OBS_V4_PREFIXES=0
TOTAL_OBS_V6_PREFIXES=0

log() {
  local level="$1"
  shift
  if [ "$VERBOSE" -ge "$level" ]; then
    printf '[%s] %s\n' "$(date +%H:%M:%S)" "$*"
  fi
}

lower() {
  printf '%s' "$1" | tr '[:upper:]' '[:lower:]'
}

upper() {
  printf '%s' "$1" | tr '[:lower:]' '[:upper:]'
}

country_name() {
  case "$(printf '%s' "$1" | tr '[:upper:]' '[:lower:]')" in
    ad) echo "Andorra" ;;
    ae) echo "United Arab Emirates" ;;
    af) echo "Afghanistan" ;;
    ag) echo "Antigua and Barbuda" ;;
    ai) echo "Anguilla" ;;
    al) echo "Albania" ;;
    am) echo "Armenia" ;;
    ao) echo "Angola" ;;
    aq) echo "Antarctica" ;;
    ar) echo "Argentina" ;;
    as) echo "American Samoa" ;;
    at) echo "Austria" ;;
    au) echo "Australia" ;;
    aw) echo "Aruba" ;;
    ax) echo "Aland Islands" ;;
    az) echo "Azerbaijan" ;;
    ba) echo "Bosnia and Herzegovina" ;;
    bb) echo "Barbados" ;;
    bd) echo "Bangladesh" ;;
    be) echo "Belgium" ;;
    bf) echo "Burkina Faso" ;;
    bg) echo "Bulgaria" ;;
    bh) echo "Bahrain" ;;
    bi) echo "Burundi" ;;
    bj) echo "Benin" ;;
    bl) echo "Saint Barthelemy" ;;
    bm) echo "Bermuda" ;;
    bn) echo "Brunei" ;;
    bo) echo "Bolivia" ;;
    bq) echo "Caribbean Netherlands" ;;
    br) echo "Brazil" ;;
    bs) echo "Bahamas" ;;
    bt) echo "Bhutan" ;;
    bv) echo "Bouvet Island" ;;
    bw) echo "Botswana" ;;
    by) echo "Belarus" ;;
    bz) echo "Belize" ;;
    ca) echo "Canada" ;;
    cc) echo "Cocos Islands" ;;
    cd) echo "Democratic Republic of the Congo" ;;
    cf) echo "Central African Republic" ;;
    cg) echo "Republic of the Congo" ;;
    ch) echo "Switzerland" ;;
    ci) echo "Cote d'Ivoire" ;;
    ck) echo "Cook Islands" ;;
    cl) echo "Chile" ;;
    cm) echo "Cameroon" ;;
    cn) echo "China" ;;
    co) echo "Colombia" ;;
    cr) echo "Costa Rica" ;;
    cu) echo "Cuba" ;;
    cv) echo "Cape Verde" ;;
    cw) echo "Curacao" ;;
    cx) echo "Christmas Island" ;;
    cy) echo "Cyprus" ;;
    cz) echo "Czechia" ;;
    de) echo "Germany" ;;
    dj) echo "Djibouti" ;;
    dk) echo "Denmark" ;;
    dm) echo "Dominica" ;;
    do) echo "Dominican Republic" ;;
    dz) echo "Algeria" ;;
    ec) echo "Ecuador" ;;
    ee) echo "Estonia" ;;
    eg) echo "Egypt" ;;
    eh) echo "Western Sahara" ;;
    er) echo "Eritrea" ;;
    es) echo "Spain" ;;
    et) echo "Ethiopia" ;;
    fi) echo "Finland" ;;
    fj) echo "Fiji" ;;
    fk) echo "Falkland Islands" ;;
    fm) echo "Micronesia" ;;
    fo) echo "Faroe Islands" ;;
    fr) echo "France" ;;
    ga) echo "Gabon" ;;
    gb) echo "United Kingdom" ;;
    gd) echo "Grenada" ;;
    ge) echo "Georgia" ;;
    gf) echo "French Guiana" ;;
    gg) echo "Guernsey" ;;
    gh) echo "Ghana" ;;
    gi) echo "Gibraltar" ;;
    gl) echo "Greenland" ;;
    gm) echo "Gambia" ;;
    gn) echo "Guinea" ;;
    gp) echo "Guadeloupe" ;;
    gq) echo "Equatorial Guinea" ;;
    gr) echo "Greece" ;;
    gs) echo "South Georgia and the South Sandwich Islands" ;;
    gt) echo "Guatemala" ;;
    gu) echo "Guam" ;;
    gw) echo "Guinea-Bissau" ;;
    gy) echo "Guyana" ;;
    hk) echo "Hong Kong" ;;
    hm) echo "Heard Island and McDonald Islands" ;;
    hn) echo "Honduras" ;;
    hr) echo "Croatia" ;;
    ht) echo "Haiti" ;;
    hu) echo "Hungary" ;;
    id) echo "Indonesia" ;;
    ie) echo "Ireland" ;;
    il) echo "Israel" ;;
    im) echo "Isle of Man" ;;
    in) echo "India" ;;
    io) echo "British Indian Ocean Territory" ;;
    iq) echo "Iraq" ;;
    ir) echo "Iran" ;;
    is) echo "Iceland" ;;
    it) echo "Italy" ;;
    je) echo "Jersey" ;;
    jm) echo "Jamaica" ;;
    jo) echo "Jordan" ;;
    jp) echo "Japan" ;;
    ke) echo "Kenya" ;;
    kg) echo "Kyrgyzstan" ;;
    kh) echo "Cambodia" ;;
    ki) echo "Kiribati" ;;
    km) echo "Comoros" ;;
    kn) echo "Saint Kitts and Nevis" ;;
    kp) echo "North Korea" ;;
    kr) echo "South Korea" ;;
    kw) echo "Kuwait" ;;
    ky) echo "Cayman Islands" ;;
    kz) echo "Kazakhstan" ;;
    la) echo "Laos" ;;
    lb) echo "Lebanon" ;;
    lc) echo "Saint Lucia" ;;
    li) echo "Liechtenstein" ;;
    lk) echo "Sri Lanka" ;;
    lr) echo "Liberia" ;;
    ls) echo "Lesotho" ;;
    lt) echo "Lithuania" ;;
    lu) echo "Luxembourg" ;;
    lv) echo "Latvia" ;;
    ly) echo "Libya" ;;
    ma) echo "Morocco" ;;
    mc) echo "Monaco" ;;
    md) echo "Moldova" ;;
    me) echo "Montenegro" ;;
    mf) echo "Saint Martin" ;;
    mg) echo "Madagascar" ;;
    mh) echo "Marshall Islands" ;;
    mk) echo "North Macedonia" ;;
    ml) echo "Mali" ;;
    mm) echo "Myanmar" ;;
    mn) echo "Mongolia" ;;
    mo) echo "Macao" ;;
    mp) echo "Northern Mariana Islands" ;;
    mq) echo "Martinique" ;;
    mr) echo "Mauritania" ;;
    ms) echo "Montserrat" ;;
    mt) echo "Malta" ;;
    mu) echo "Mauritius" ;;
    mv) echo "Maldives" ;;
    mw) echo "Malawi" ;;
    mx) echo "Mexico" ;;
    my) echo "Malaysia" ;;
    mz) echo "Mozambique" ;;
    na) echo "Namibia" ;;
    nc) echo "New Caledonia" ;;
    ne) echo "Niger" ;;
    nf) echo "Norfolk Island" ;;
    ng) echo "Nigeria" ;;
    ni) echo "Nicaragua" ;;
    nl) echo "Netherlands" ;;
    no) echo "Norway" ;;
    np) echo "Nepal" ;;
    nr) echo "Nauru" ;;
    nu) echo "Niue" ;;
    nz) echo "New Zealand" ;;
    om) echo "Oman" ;;
    pa) echo "Panama" ;;
    pe) echo "Peru" ;;
    pf) echo "French Polynesia" ;;
    pg) echo "Papua New Guinea" ;;
    ph) echo "Philippines" ;;
    pk) echo "Pakistan" ;;
    pl) echo "Poland" ;;
    pm) echo "Saint Pierre and Miquelon" ;;
    pn) echo "Pitcairn Islands" ;;
    pr) echo "Puerto Rico" ;;
    ps) echo "Palestine" ;;
    pt) echo "Portugal" ;;
    pw) echo "Palau" ;;
    py) echo "Paraguay" ;;
    qa) echo "Qatar" ;;
    re) echo "Reunion" ;;
    ro) echo "Romania" ;;
    rs) echo "Serbia" ;;
    ru) echo "Russia" ;;
    rw) echo "Rwanda" ;;
    sa) echo "Saudi Arabia" ;;
    sb) echo "Solomon Islands" ;;
    sc) echo "Seychelles" ;;
    sd) echo "Sudan" ;;
    se) echo "Sweden" ;;
    sg) echo "Singapore" ;;
    sh) echo "Saint Helena" ;;
    si) echo "Slovenia" ;;
    sj) echo "Svalbard and Jan Mayen" ;;
    sk) echo "Slovakia" ;;
    sl) echo "Sierra Leone" ;;
    sm) echo "San Marino" ;;
    sn) echo "Senegal" ;;
    so) echo "Somalia" ;;
    sr) echo "Suriname" ;;
    ss) echo "South Sudan" ;;
    st) echo "Sao Tome and Principe" ;;
    sv) echo "El Salvador" ;;
    sx) echo "Sint Maarten" ;;
    sy) echo "Syria" ;;
    sz) echo "Eswatini" ;;
    tc) echo "Turks and Caicos Islands" ;;
    td) echo "Chad" ;;
    tf) echo "French Southern Territories" ;;
    tg) echo "Togo" ;;
    th) echo "Thailand" ;;
    tj) echo "Tajikistan" ;;
    tk) echo "Tokelau" ;;
    tl) echo "Timor-Leste" ;;
    tm) echo "Turkmenistan" ;;
    tn) echo "Tunisia" ;;
    to) echo "Tonga" ;;
    tr) echo "Turkey" ;;
    tt) echo "Trinidad and Tobago" ;;
    tv) echo "Tuvalu" ;;
    tw) echo "Taiwan" ;;
    tz) echo "Tanzania" ;;
    ua) echo "Ukraine" ;;
    ug) echo "Uganda" ;;
    um) echo "United States Minor Outlying Islands" ;;
    us) echo "United States" ;;
    uy) echo "Uruguay" ;;
    uz) echo "Uzbekistan" ;;
    va) echo "Vatican City" ;;
    vc) echo "Saint Vincent and the Grenadines" ;;
    ve) echo "Venezuela" ;;
    vg) echo "British Virgin Islands" ;;
    vi) echo "U.S. Virgin Islands" ;;
    vn) echo "Vietnam" ;;
    vu) echo "Vanuatu" ;;
    wf) echo "Wallis and Futuna" ;;
    ws) echo "Samoa" ;;
    ye) echo "Yemen" ;;
    yt) echo "Mayotte" ;;
    za) echo "South Africa" ;;
    zm) echo "Zambia" ;;
    zw) echo "Zimbabwe" ;;
    *) echo "$(upper "$1")" ;;
  esac
}

get_all_countries() {
  curl -fsSL "$BASE_API_URL" \
    | grep -oE '"name":[[:space:]]*"[^"]+"' \
    | sed -E 's/"name":[[:space:]]*"([^"]+)"/\1/' \
    | tr '[:upper:]' '[:lower:]' \
    | grep -E '^[a-z]{2}$' \
    | sort -u
}

fetch_country_file() {
  local cc="$1"
  local family="$2"
  local out="$3"
  curl -fsSL "${BASE_RAW_URL}/${cc}/${family}-aggregated.txt" -o "$out"
}

count_prefixes() {
  local file="$1"
  grep -Evc '^[[:space:]]*(#|$)' "$file" || true
}

in_array() {
  local needle="$1"
  shift
  local item
  for item in "$@"; do
    [ "$item" = "$needle" ] && return 0
  done
  return 1
}

emit_header() {
  cat <<'EOF'
# ============================================================
# Generated MikroTik script
# Source: ipverse country-ip-blocks
# IPv4 + IPv6
# BLOCK  = raw prerouting drop
# OBSERVE= mangle prerouting passthrough connection-state=new
# ============================================================

:put "Generated geo policy import starting"

EOF
}

emit_purge_rules() {
  cat <<'EOF'
:put "Purging geo-* rules only"

/ip firewall raw remove [find where comment~"^geo-"]
/ipv6 firewall raw remove [find where comment~"^geo-"]
/ip firewall mangle remove [find where comment~"^geo-"]
/ipv6 firewall mangle remove [find where comment~"^geo-"]

EOF
}

emit_remove_country_lists() {
  local list="$1"
  local family="$2"
  if [ "$family" = "ipv4" ]; then
    cat <<EOF
/ip firewall address-list remove [find where list="$list"]

EOF
  else
    cat <<EOF
/ipv6 firewall address-list remove [find where list="$list"]

EOF
  fi
}

emit_v4_entries_replace() {
  local cc="$1"
  local list="$2"
  local file="$3"
  local cname
  cname="$(country_name "$cc")"
  while IFS= read -r prefix; do
    [[ -z "${prefix// }" ]] && continue
    [[ "$prefix" =~ ^[[:space:]]*# ]] && continue
    printf '/ip firewall address-list add list="%s" address=%s comment="%s"\n' "$list" "$prefix" "$cname"
  done < "$file"
  printf '\n'
}

emit_v6_entries_replace() {
  local cc="$1"
  local list="$2"
  local file="$3"
  local cname
  cname="$(country_name "$cc")"
  while IFS= read -r prefix; do
    [[ -z "${prefix// }" ]] && continue
    [[ "$prefix" =~ ^[[:space:]]*# ]] && continue
    printf '/ipv6 firewall address-list add list="%s" address=%s comment="%s"\n' "$list" "$prefix" "$cname"
  done < "$file"
  printf '\n'
}

emit_v4_entries_keep() {
  local cc="$1"
  local list="$2"
  local file="$3"
  local cname
  cname="$(country_name "$cc")"
  while IFS= read -r prefix; do
    [[ -z "${prefix// }" ]] && continue
    [[ "$prefix" =~ ^[[:space:]]*# ]] && continue
    printf ':if ([:len [/ip firewall address-list find where list="%s" and address=%s]] = 0) do={ /ip firewall address-list add list="%s" address=%s comment="%s" }\n' \
      "$list" "$prefix" "$list" "$prefix" "$cname"
  done < "$file"
  printf '\n'
}

emit_v6_entries_keep() {
  local cc="$1"
  local list="$2"
  local file="$3"
  local cname
  cname="$(country_name "$cc")"
  while IFS= read -r prefix; do
    [[ -z "${prefix// }" ]] && continue
    [[ "$prefix" =~ ^[[:space:]]*# ]] && continue
    printf ':if ([:len [/ipv6 firewall address-list find where list="%s" and address=%s]] = 0) do={ /ipv6 firewall address-list add list="%s" address=%s comment="%s" }\n' \
      "$list" "$prefix" "$list" "$prefix" "$cname"
  done < "$file"
  printf '\n'
}

emit_block_v4_rule() {
  local cc="$1"
  local list="$2"
  local cname
  cname="$(country_name "$cc")"
  local comment="geo-block-${cc}-v4 ${cname}"
  cat <<EOF
:if ([:len [/ip firewall raw find where comment="$comment"]] = 0) do={
    /ip firewall raw add chain=prerouting in-interface-list="$WAN_LIST" src-address-list="$list" action=drop comment="$comment"
}

EOF
}

emit_block_v6_rule() {
  local cc="$1"
  local list="$2"
  local cname
  cname="$(country_name "$cc")"
  local comment="geo-block-${cc}-v6 ${cname}"
  cat <<EOF
:if ([:len [/ipv6 firewall raw find where comment="$comment"]] = 0) do={
    /ipv6 firewall raw add chain=prerouting in-interface-list="$WAN_LIST" src-address-list="$list" action=drop comment="$comment"
}

EOF
}

emit_obs_v4_rule() {
  local cc="$1"
  local list="$2"
  local cname
  cname="$(country_name "$cc")"
  local comment="geo-ip-${cc}-v4 ${cname}"
  cat <<EOF
:if ([:len [/ip firewall mangle find where comment="$comment"]] = 0) do={
    /ip firewall mangle add chain=prerouting in-interface-list="$WAN_LIST" connection-state=new src-address-list="$list" action=passthrough comment="$comment"
}

EOF
}

emit_obs_v6_rule() {
  local cc="$1"
  local list="$2"
  local cname
  cname="$(country_name "$cc")"
  local comment="geo-ip-${cc}-v6 ${cname}"
  cat <<EOF
:if ([:len [/ipv6 firewall mangle find where comment="$comment"]] = 0) do={
    /ipv6 firewall mangle add chain=prerouting in-interface-list="$WAN_LIST" connection-state=new src-address-list="$list" action=passthrough comment="$comment"
}

EOF
}

log 1 "Debut generation"
log 1 "Pays bloques: ${BLOCK_COUNTRIES[*]}"
log 1 "Observe all others: $OBSERVE_ALL_OTHERS"
log 1 "Mode: $MODE | Purge rules only: $PURGE_RULES_ONLY | Purge only: $PURGE_ONLY | WAN list: $WAN_LIST | Output: $OUTPUT"

ALL_COUNTRIES=()
if [ "$OBSERVE_ALL_OTHERS" = "yes" ]; then
  log 1 "Recuperation de la liste complete des pays depuis GitHub API"
  mapfile -t ALL_COUNTRIES < <(get_all_countries)
  log 1 "Pays disponibles dans le depot: ${#ALL_COUNTRIES[@]}"
else
  ALL_COUNTRIES=("${BLOCK_COUNTRIES[@]}")
fi

BLOCK_SET=()
for cc in "${BLOCK_COUNTRIES[@]}"; do
  BLOCK_SET+=("$(lower "$cc")")
done

OBSERVE_COUNTRIES=()
if [ "$OBSERVE_ALL_OTHERS" = "yes" ]; then
  for cc in "${ALL_COUNTRIES[@]}"; do
    if ! in_array "$cc" "${BLOCK_SET[@]}"; then
      OBSERVE_COUNTRIES+=("$cc")
    fi
  done
fi

log 1 "Pays observes: ${#OBSERVE_COUNTRIES[@]}"

emit_header > "$OUTPUT"

if [ "$PURGE_RULES_ONLY" = "yes" ] || [ "$PURGE_ONLY" = "yes" ]; then
  log 1 "Ajout du bloc de purge des regles geo-*"
  emit_purge_rules >> "$OUTPUT"
fi

if [ "$PURGE_ONLY" = "yes" ]; then
  {
    echo ':put "Generated geo policy purge done"'
    echo
  } >> "$OUTPUT"
  log 1 "Script de purge uniquement genere: $OUTPUT"
  exit 0
fi

index=0
for raw_cc in "${BLOCK_SET[@]}"; do
  index=$((index + 1))
  cc="$(lower "$raw_cc")"
  cname="$(country_name "$cc")"

  v4file="$WORKDIR/${cc}-block-ipv4.txt"
  v6file="$WORKDIR/${cc}-block-ipv6.txt"

  v4list="geo-country-${cc}-v4"
  v6list="geo-country-${cc}-v6"

  v4count=0
  v6count=0

  log 1 "[BLOCK $index/${#BLOCK_SET[@]}] $cname (${cc^^})"

  if fetch_country_file "$cc" "ipv4" "$v4file" 2>/dev/null; then
    v4count="$(count_prefixes "$v4file")"
    log 2 "  IPv4 telecharge, prefixes: $v4count"
  else
    log 2 "  IPv4 absent ou telechargement en echec"
  fi

  if fetch_country_file "$cc" "ipv6" "$v6file" 2>/dev/null; then
    v6count="$(count_prefixes "$v6file")"
    log 2 "  IPv6 telecharge, prefixes: $v6count"
  else
    log 2 "  IPv6 absent ou telechargement en echec"
  fi

  {
    echo ":put \"BLOCK $cname ($(upper "$cc")) : IPv4=$v4count IPv6=$v6count\""
    echo
  } >> "$OUTPUT"

  if [ "$v4count" -gt 0 ]; then
    TOTAL_BLOCK_V4_LISTS=$((TOTAL_BLOCK_V4_LISTS + 1))
    TOTAL_BLOCK_V4_PREFIXES=$((TOTAL_BLOCK_V4_PREFIXES + v4count))
    log 1 "  BLOCK IPv4: $v4count -> $v4list"

    if [ "$MODE" = "replace" ]; then
      emit_remove_country_lists "$v4list" "ipv4" >> "$OUTPUT"
      emit_v4_entries_replace "$cc" "$v4list" "$v4file" >> "$OUTPUT"
    else
      emit_v4_entries_keep "$cc" "$v4list" "$v4file" >> "$OUTPUT"
    fi
    emit_block_v4_rule "$cc" "$v4list" >> "$OUTPUT"
  else
    log 1 "  BLOCK IPv4: aucun prefixe"
  fi

  if [ "$v6count" -gt 0 ]; then
    TOTAL_BLOCK_V6_LISTS=$((TOTAL_BLOCK_V6_LISTS + 1))
    TOTAL_BLOCK_V6_PREFIXES=$((TOTAL_BLOCK_V6_PREFIXES + v6count))
    log 1 "  BLOCK IPv6: $v6count -> $v6list"

    if [ "$MODE" = "replace" ]; then
      emit_remove_country_lists "$v6list" "ipv6" >> "$OUTPUT"
      emit_v6_entries_replace "$cc" "$v6list" "$v6file" >> "$OUTPUT"
    else
      emit_v6_entries_keep "$cc" "$v6list" "$v6file" >> "$OUTPUT"
    fi
    emit_block_v6_rule "$cc" "$v6list" >> "$OUTPUT"
  else
    log 1 "  BLOCK IPv6: aucun prefixe"
  fi
done

index=0
for raw_cc in "${OBSERVE_COUNTRIES[@]}"; do
  index=$((index + 1))
  cc="$(lower "$raw_cc")"
  cname="$(country_name "$cc")"

  v4file="$WORKDIR/${cc}-obs-ipv4.txt"
  v6file="$WORKDIR/${cc}-obs-ipv6.txt"

  v4list="geo-country-${cc}-v4"
  v6list="geo-country-${cc}-v6"

  v4count=0
  v6count=0

  log 1 "[OBS $index/${#OBSERVE_COUNTRIES[@]}] $cname (${cc^^})"

  if fetch_country_file "$cc" "ipv4" "$v4file" 2>/dev/null; then
    v4count="$(count_prefixes "$v4file")"
    log 2 "  IPv4 telecharge, prefixes: $v4count"
  else
    log 2 "  IPv4 absent ou telechargement en echec"
  fi

  if fetch_country_file "$cc" "ipv6" "$v6file" 2>/dev/null; then
    v6count="$(count_prefixes "$v6file")"
    log 2 "  IPv6 telecharge, prefixes: $v6count"
  else
    log 2 "  IPv6 absent ou telechargement en echec"
  fi

  {
    echo ":put \"OBSERVE $cname ($(upper "$cc")) : IPv4=$v4count IPv6=$v6count\""
    echo
  } >> "$OUTPUT"

  if [ "$v4count" -gt 0 ]; then
    TOTAL_OBS_V4_LISTS=$((TOTAL_OBS_V4_LISTS + 1))
    TOTAL_OBS_V4_PREFIXES=$((TOTAL_OBS_V4_PREFIXES + v4count))
    log 1 "  OBS IPv4: $v4count -> $v4list"

    if [ "$MODE" = "replace" ]; then
      emit_remove_country_lists "$v4list" "ipv4" >> "$OUTPUT"
      emit_v4_entries_replace "$cc" "$v4list" "$v4file" >> "$OUTPUT"
    else
      emit_v4_entries_keep "$cc" "$v4list" "$v4file" >> "$OUTPUT"
    fi
    emit_obs_v4_rule "$cc" "$v4list" >> "$OUTPUT"
  else
    log 1 "  OBS IPv4: aucun prefixe"
  fi

  if [ "$v6count" -gt 0 ]; then
    TOTAL_OBS_V6_LISTS=$((TOTAL_OBS_V6_LISTS + 1))
    TOTAL_OBS_V6_PREFIXES=$((TOTAL_OBS_V6_PREFIXES + v6count))
    log 1 "  OBS IPv6: $v6count -> $v6list"

    if [ "$MODE" = "replace" ]; then
      emit_remove_country_lists "$v6list" "ipv6" >> "$OUTPUT"
      emit_v6_entries_replace "$cc" "$v6list" "$v6file" >> "$OUTPUT"
    else
      emit_v6_entries_keep "$cc" "$v6list" "$v6file" >> "$OUTPUT"
    fi
    emit_obs_v6_rule "$cc" "$v6list" >> "$OUTPUT"
  else
    log 1 "  OBS IPv6: aucun prefixe"
  fi
done

{
  echo ':put "Generated geo policy import done"'
  echo
} >> "$OUTPUT"

log 1 "Generation terminee"
log 1 "BLOCK IPv4 listes   : $TOTAL_BLOCK_V4_LISTS"
log 1 "BLOCK IPv6 listes   : $TOTAL_BLOCK_V6_LISTS"
log 1 "OBS IPv4 listes     : $TOTAL_OBS_V4_LISTS"
log 1 "OBS IPv6 listes     : $TOTAL_OBS_V6_LISTS"
log 1 "BLOCK IPv4 prefixes : $TOTAL_BLOCK_V4_PREFIXES"
log 1 "BLOCK IPv6 prefixes : $TOTAL_BLOCK_V6_PREFIXES"
log 1 "OBS IPv4 prefixes   : $TOTAL_OBS_V4_PREFIXES"
log 1 "OBS IPv6 prefixes   : $TOTAL_OBS_V6_PREFIXES"
log 1 "Fichier genere      : $OUTPUT"